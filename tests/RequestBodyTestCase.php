<?php


namespace Pada\RequestBodyBundle\Tests;


use Pada\Reflection\Scanner\Scanner;
use Pada\RequestBodyBundle\ArgumentResolver\RequestBodyResolver;
use Pada\RequestBodyBundle\Cache\RequestBodyCacheWarmer;
use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Pada\RequestBodyBundle\EventListener\RequestBodyListener;
use Pada\RequestBodyBundle\Service\RequestBodyService;
use PHPStan\Testing\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
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
        $dir = getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures';
        $cache = new ArrayAdapter();
        $warmer = new RequestBodyCacheWarmer(new Scanner(), new ParameterBag(['kernel.project_dir' => $dir]), $cache);
        $warmer->throwException(false);
        $warmer->warmUp('');

        $this->kernel = $this->getMockBuilder(HttpKernelInterface::class)
            ->disableOriginalConstructor()
            ->getMock();


        $requestBodyService = new RequestBodyService($cache, $this->createSerializer(), $this->createValidator());

        $this->listener = new RequestBodyListener($requestBodyService);

        $this->resolver = new RequestBodyResolver($requestBodyService);
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
