<?php


namespace Pada\RequestBodyBundle\Service;


use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Pada\RequestBodyBundle\Exception\DeserializationException;
use Pada\RequestBodyBundle\Exception\ValidationException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestBodyService implements RequestBodyServiceInterface
{
    private CacheItemPoolInterface $cacheSystem;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(CacheItemPoolInterface $cacheSystem, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->cacheSystem = $cacheSystem;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function isRequestSupported(Request $request): bool
    {
        return \in_array($request->getMethod(), ['PUT', 'PATCH', 'POST', 'DELETE']);
    }

    public function processEvent($controller, string $method, ControllerEvent $controllerEvent): void
    {
        try {
            if (\is_object($controller)) {
                $claz = \get_class($controller);
            } else {
                $claz = $controller;
            }
            $key = \md5($claz . '_' . $method);
            $cachedItem = $this->cacheSystem->getItem($key);

            if (!$cachedItem->isHit()) {
                return;
            }

            /** @var RequestBody $requestBody */
            $requestBody = $cachedItem->get();

            $this->doProcess($controllerEvent, $requestBody);

        } catch (\ReflectionException | \LogicException $e) {
            self::throwServerException($e, $controller, $method);
        }
    }

    /**
     * @param Request $request
     * @param RequestBody $requestBody
     * @return mixed
     */
    public function deserializeRequest(Request $request, RequestBody $requestBody)
    {
        try {
            return $this->serializer->deserialize(
                $request->getContent(),
                $requestBody->type,
                $requestBody->getSerializationFormat(),
                $requestBody->deserializationContext
            );
        } catch (\Throwable $throwable) {
            throw new DeserializationException(
                $requestBody->deserializationError ??
                \sprintf(
                    'Could not deserialize request body from [format]=%s to [type]=%s. %s',
                    $requestBody->getSerializationFormat(),
                    $requestBody->type,
                    $throwable->getMessage()
                ),
                $throwable
            );
        }
    }

    /**
     * @param mixed $target
     * @param RequestBody $requestBody
     */
    public function validateTarget($target, RequestBody $requestBody): void
    {
        if(empty($requestBody->validationGroups)) {
            return;
        }

        $validationGroups = $requestBody->validationGroups;

        if (1 === \count($validationGroups) && 'all' === \strtolower($validationGroups[0])) {
            // validationGroups={"all"}
            $validationErrors = $this->validator->validate($target);
        } else {
            // Specific validation groups
            $validationErrors = $this->validator->validate($target, null, $validationGroups);
        }

        if ($validationErrors->count() > 0) {
            throw new ValidationException(
                $validationErrors,
                $requestBody->validationError ?? 'Transfer data object is not valid'
            );
        }
    }

    private function doProcess(ControllerEvent $event, RequestBody $requestBody): void
    {
        if (empty($event->getRequest()->getContent())) {
            throw new BadRequestHttpException('The request body is empty.');
        }

        // Consumes

        if (empty($requestBody->consumes)) {
            $requestBody->consumes = $this->getContentType($event->getRequest());
        }

        if (false === RequestBody::supports($requestBody->consumes)) {
            throw new \LogicException(
                "Unsupported content type `$requestBody->consumes`."
            );
        }

        if (false === \class_exists($requestBody->type)) {
            throw new \LogicException("Type not found `$requestBody->type`.");
        }

        $event->getRequest()->attributes->set(RequestBody::REQUEST_ATTRIBUTE, $requestBody);
    }

    private function getContentType(Request $request): string
    {
        $requestContentType = '';

        if ($request->headers->has('Content-Type') && '*/*' !== $request->headers->get('Content-Type')) {
            $requestContentType = $request->headers->get('Content-Type');
        }

        if (empty($requestContentType)) {
            throw new BadRequestHttpException(
                "Could not detect media type by client request. Client must specify the `Content-Type` header."
            );
        }

        return $requestContentType;
    }

    /**
     * @param \Exception $exception
     * @param mixed $controller
     * @param string $method
     */
    public static function throwServerException(\Exception $exception, $controller, string $method): void
    {
        $controllerClass = \get_class($controller);

        $annotationClass = self::getClassName(RequestBody::class);

        throw new HttpException(
            500,
            \sprintf(
                'Failed to process annotation @%s at %s->%s(%s). %s',
                $annotationClass,
                $controllerClass,
                $method,
                self::stringifyMethodArguments($controller, $method),
                $exception->getMessage()
            ),
            $exception
        );
    }

    private static function getClassName(string $class): string
    {
        $parts = \explode('\\', $class);
        return \end($parts);
    }

    /**
     * @param mixed $controller
     * @param string $method
     * @return string
     */
    private static function stringifyMethodArguments($controller, string $method): string
    {
        try {
            $reflection = new \ReflectionMethod($controller, $method);

            return \implode(',', \array_map(static function (\ReflectionParameter $parameter) {
                $type = $parameter->getType();

                if ($type instanceof \ReflectionNamedType) {
                    $type = $type->getName();
                }

                return \sprintf('<%s>%s', $type ?? '', $parameter->getName());
            }, $reflection->getParameters()));

        } catch (\ReflectionException $exception) {
            return '';
        }
    }
}
