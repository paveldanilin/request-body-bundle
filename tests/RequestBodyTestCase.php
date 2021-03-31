<?php


namespace paveldanilin\RequestBodyBundle\Tests;


use Doctrine\Common\Annotations\AnnotationReader;
use paveldanilin\RequestBodyBundle\ArgumentResolver\RequestBodyResolver;
use paveldanilin\RequestBodyBundle\Controller\Annotation\RequestBody;
use paveldanilin\RequestBodyBundle\EventListener\RequestBodyListener;
use PHPStan\Testing\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class RequestBodyTestCase extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|HttpKernelInterface */
    protected $kernel;

    protected RequestBodyListener $listener;
    protected RequestBodyResolver $resolver;


    protected function init(): void
    {
        $this->kernel = $this->getMockBuilder(HttpKernelInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RequestBodyListener(new AnnotationReader());

        $this->resolver = new RequestBodyResolver($this->createSerializer(), $this->createValidator());
    }

    /**
     * @param mixed $controller
     * @param string $method
     * @param Request $request
     * @return ControllerEvent
     */
    protected function createControllerEvent($controller, string $method, Request $request): ControllerEvent
    {
        return new ControllerEvent(
            $this->kernel,
            [$controller, $method],
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    protected function createRequest(string $method, string $content, array $headers): Request
    {
        $request = new Request([], [], [], [], [], [], $content);

        $request->setMethod($method);

        foreach ($headers as $key => $header) {
            $request->headers->set($key, $header);
        }

        return $request;
    }

    protected function createUserJsonRequest(): Request
    {
        return $this->createRequest('POST', '{"name": "testUserName"}', [
            'content-type' => RequestBody::APPLICATION_JSON,
        ]);
    }

    protected function createBadUserJsonRequest(): Request
    {
        return $this->createRequest('POST', '{}', [
            'content-type' => RequestBody::APPLICATION_JSON,
        ]);
    }

    private function createSerializer(): Serializer
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];

        $normalizers = [new ObjectNormalizer()];

        return new Serializer($normalizers, $encoders);
    }

    private function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
    }
}
