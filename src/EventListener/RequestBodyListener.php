<?php


namespace paveldanilin\RequestBodyBundle\EventListener;

use paveldanilin\RequestBodyBundle\Annotation\RequestBody;
use Doctrine\Common\Annotations\Reader;
use ReflectionParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class RequestBodyListener
{
    /** @var Reader */
    private $reader;

    public function __construct(Reader $annotationReader)
    {
        $this->reader = $annotationReader;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $controllerMeta = $event->getController();

        if (false === \is_array($controllerMeta)) {
            return;
        }

        [$controller, $method] = $controllerMeta;

        try {
            $reflectionMethod = new \ReflectionMethod($controller, $method);

            /** @var RequestBody|null $requestBody */
            $requestBody = $this->reader->getMethodAnnotation($reflectionMethod,RequestBody::class);

            if (null === $requestBody) {
                return;
            }

            $this->process($event, $requestBody, $reflectionMethod);

        } catch (\ReflectionException $e) {
            $controllerClass = \get_class($controller);
            $annotationClass = $this->getClassName(RequestBody::class);
            throw new \RuntimeException(
                "Failed to read method annotation @$annotationClass at $controllerClass.$method(): {$e->getMessage()}."
            );
        } catch (\LogicException $e) {
            $controllerClass = \get_class($controller);
            $annotationClass = $this->getClassName(RequestBody::class);
            throw new \RuntimeException(
                "Failed to process method annotation @$annotationClass at $controllerClass.$method(): {$e->getMessage()}.",
                0,
                $e
            );
        }
    }

    private function process(ControllerEvent $event, RequestBody $requestBody, \ReflectionMethod $method): void
    {
        if (empty($event->getRequest()->getContent())) {
            throw new BadRequestHttpException('The request body is empty.');
        }

        if (empty($requestBody->consumes)) {
            $requestBody->consumes = $this->detectConsumesMediaType($event->getRequest());
        } elseif (false === RequestBody::supports($requestBody->consumes)) {
            throw new \LogicException(
                "Unsupported content type `$requestBody->consumes`."
            );
        }

        if (empty($requestBody->type)) {
            $requestBody->type = $this->getParameterType($method->getParameters(), $requestBody->param);
        } elseif (false === \class_exists($requestBody->type)) {
            throw new \LogicException("Type not found `$requestBody->type`.");
        }

        $event->getRequest()->attributes->set(RequestBody::REQUEST_ATTRIBUTE, $requestBody);
    }

    private function detectConsumesMediaType(Request $request): string
    {
        $requestedMediaType = '';

        if ($request->headers->has('Content-Type') && '*/*' !== $request->headers->get('Content-Type')) {
            $requestedMediaType = $request->headers->get('Content-Type');
        }
        /*elseif ($request->headers->has('Accept') && '*\/*' !== $request->headers->get('Accept')) {
            $requestedMediaType = $request->headers->get('Accept');
        }*/

        if (empty($requestedMediaType)) {
            throw new BadRequestHttpException(
                "Could not detect media type by client request. Client must specify the `Content-Type` header."
            );
        }

        if (false === RequestBody::supports($requestedMediaType)) {
            throw new \RuntimeException("Unsupported content type `$requestedMediaType`.");
        }

        return $requestedMediaType;
    }

    /**
     * @param array<\ReflectionParameter> $parameters
     * @param string $parameterName
     * @return string
     * @throws \LogicException
     */
    private function getParameterType(array $parameters, string $parameterName): string
    {
        foreach ($parameters as $parameter) {
            if ($parameter->getName() === $parameterName) {
                return $this->getType($parameter);
            }
        }

        throw new \LogicException(
            "Parameter not found `$parameterName`."
        );
    }

    private function getType(ReflectionParameter $parameter): string
    {
        if (false === $parameter->hasType()) {
            throw new \LogicException(
                "Parameter does not have type hint `{$parameter->name}`."
            );
        }

        $type = (string)$parameter->getType();

        if (false === \class_exists($type)) {
            throw new \LogicException("Type not found `$type`.");
        }

        return $type;
    }

    private function getClassName(string $class): string
    {
        $parts = \explode('\\', $class);
        return \end($parts);
    }
}
