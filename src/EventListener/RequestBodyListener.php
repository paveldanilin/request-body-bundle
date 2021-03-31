<?php


namespace paveldanilin\RequestBodyBundle\EventListener;

use paveldanilin\RequestBodyBundle\Controller\Annotation\RequestBody;
use Doctrine\Common\Annotations\Reader;
use ReflectionParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class RequestBodyListener
{
    private Reader $reader;

    public function __construct(Reader $annotationReader)
    {
        $this->reader = $annotationReader;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest() || !$this->isHttpMethodSupported($event->getRequest())) {
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

        } catch (\ReflectionException | \LogicException $e) {
            $this->throwServerException($e, $controller, $method);
        }
    }

    private function isHttpMethodSupported(Request $request): bool
    {
        return \in_array($request->getMethod(), ['PUT', 'PATCH', 'POST', 'DELETE']);
    }

    /**
     * @param \Exception $exception
     * @param mixed $controller
     * @param string $method
     */
    private function throwServerException(\Exception $exception, $controller, string $method): void
    {
        $controllerClass = \get_class($controller);

        $annotationClass = $this->getClassName(RequestBody::class);

        throw new HttpException(
            500,
            \sprintf(
                'Failed to process annotation @%s at %s->%s(%s). %s',
                $annotationClass,
                $controllerClass,
                $method,
                $this->stringifyMethodArguments($controller, $method),
                $exception->getMessage()
            ),
            $exception
        );
    }

    /**
     * @param mixed $controller
     * @param string $method
     * @return string
     */
    private function stringifyMethodArguments($controller, string $method): string
    {
        try {
            $reflection = new \ReflectionMethod($controller, $method);

            return \implode(',', \array_map(static function (ReflectionParameter $parameter) {
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

    private function process(ControllerEvent $event, RequestBody $requestBody, \ReflectionMethod $method): void
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

        // Param

        if (empty($requestBody->param)) {
            $requestBody->param = $this->getParam($method);
        }

        // Type

        if (empty($requestBody->type)) {
            $requestBody->type = $this->getParameterType($method->getParameters(), $requestBody->param);
        }

        if (false === \class_exists($requestBody->type)) {
            throw new \LogicException("Type not found `$requestBody->type`.");
        }

        $event->getRequest()->attributes->set(RequestBody::REQUEST_ATTRIBUTE, $requestBody);
    }

    private function getParam(\ReflectionMethod $method): string
    {
        $numOfParams = $method->getNumberOfParameters();

        if (0 === $numOfParams) {
            throw new \LogicException('Could not autodetect parameter for body mapping. The method does not have parameters.');
        }

        if (1 < $numOfParams) {
            throw new \LogicException('Could not autodetect parameter for body mapping. The method has too many parameters.');
        }

        return $method->getParameters()[0]->getName();
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
            "Parameter `$parameterName` not found."
        );
    }

    private function getType(ReflectionParameter $parameter): string
    {
        if (false === $parameter->hasType() || null === $parameter->getType()) {
            throw new \LogicException(
                "Parameter `{$parameter->name}` does not have type hint."
            );
        }

        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        throw new \LogicException(
            "Parameter `{$parameter->name}` does not have type hint."
        );
    }

    private function getClassName(string $class): string
    {
        $parts = \explode('\\', $class);
        return \end($parts);
    }
}
