<?php


namespace Pada\RequestBodyBundle\Tests\EventListener;


use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Pada\RequestBodyBundle\Tests\Fixtures\TestUserController;
use Pada\RequestBodyBundle\Tests\Fixtures\User;
use Pada\RequestBodyBundle\Tests\RequestBodyTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestBodyListenerTest extends RequestBodyTestCase
{
    protected function setUp(): void
    {
        $this->init();
    }

    public function testPostRequestListener(): void
    {
        $request = $this->createUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'createUser', $request);

        $this->listener->onKernelController($event);

        self::assertCount(1, $request->attributes->all());

        /** @var RequestBody $requestBody */
        $requestBody = $request->attributes->get(RequestBody::REQUEST_ATTRIBUTE);

        self::assertEquals('user', $requestBody->param);
        self::assertEquals('application/json', $requestBody->consumes);
        self::assertEquals(User::class, $requestBody->type);
    }

    public function testAutoMap(): void
    {
        $request = $this->createUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'autoMap', $request);

        $this->listener->onKernelController($event);

        self::assertCount(1, $request->attributes->all());

        /** @var RequestBody $requestBody */
        $requestBody = $request->attributes->get(RequestBody::REQUEST_ATTRIBUTE);

        self::assertEquals('user', $requestBody->param);
    }

    public function testAutoMapNoParams(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Failed to process annotation @RequestBody at Pada\RequestBodyBundle\Tests\Fixtures\TestUserController->autoMapNoParams(). Could not autodetect parameter for body mapping. The method does not have parameters.');

        $request = $this->createUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'autoMapNoParams', $request);

        $this->listener->onKernelController($event);
    }

    public function testAutoMapToManyParams(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Failed to process annotation @RequestBody at Pada\RequestBodyBundle\Tests\Fixtures\TestUserController->autoMapTooManyParams(<int>a,<int>b). Could not autodetect parameter for body mapping. The method has too many parameters.');

        $request = $this->createUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'autoMapTooManyParams', $request);

        $this->listener->onKernelController($event);
    }

    public function testRequestWithoutContentType(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Could not detect media type by client request. Client must specify the `Content-Type` header.');

        $request = $this->createRequest('POST', '{}', []);

        $event = $this->createControllerEvent(new TestUserController(), 'createUser', $request);

        $this->listener->onKernelController($event);
    }

    public function testRequestWithoutBody(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The request body is empty.');

        $request = $this->createRequest('POST', '', []);

        $event = $this->createControllerEvent(new TestUserController(), 'createUser', $request);

        $this->listener->onKernelController($event);
    }

    public function testParameterNotFound(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Failed to process annotation @RequestBody at Pada\RequestBodyBundle\Tests\Fixtures\TestUserController->editUser(<Pada\RequestBodyBundle\Tests\Fixtures\User>u). Parameter `user` not found.');

        $request = $this->createUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'editUser', $request);

        $this->listener->onKernelController($event);
    }

    public function testNoTypeHint(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Failed to process annotation @RequestBody at Pada\RequestBodyBundle\Tests\Fixtures\TestUserController->noTypeHint(<>user). Parameter `user` does not have type hint.');

        $request = $this->createUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'noTypeHint', $request);

        $this->listener->onKernelController($event);
    }
}
