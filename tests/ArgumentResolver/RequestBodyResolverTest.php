<?php


namespace Pada\RequestBodyBundle\Tests\ArgumentResolver;


use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Pada\RequestBodyBundle\Exception\ValidationException;
use Pada\RequestBodyBundle\Tests\Fixtures\TestUserController;
use Pada\RequestBodyBundle\Tests\Fixtures\User;
use Pada\RequestBodyBundle\Tests\RequestBodyTestCase;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class RequestBodyResolverTest extends RequestBodyTestCase
{
    protected function setUp(): void
    {
        $this->init();
    }

    public function testResolverSupports(): void
    {
        $request = $this->createUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'createUser', $request);

        $this->listener->onKernelController($event);

        /** @var RequestBody $requestBody */
        $requestBody = $request->attributes->get(RequestBody::REQUEST_ATTRIBUTE);

        $param = $this->getMockBuilder(ArgumentMetadata::class)->disableOriginalConstructor()->getMock();
        $param->expects(self::once())->method('getName')->willReturn($requestBody->param);
        $param->expects(self::once())->method('getType')->willReturn(User::class);

        self::assertTrue($this->resolver->supports($request, $param));
    }

    public function testResolve(): void
    {
        $request = $this->createUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'createUser', $request);

        $this->listener->onKernelController($event);

        $param = $this->getMockBuilder(ArgumentMetadata::class)->disableOriginalConstructor()->getMock();

        $user = $this->resolver->resolve($request, $param);

        self::assertInstanceOf(User::class, $user->current());

        self::assertEquals('testUserName', $user->current()->name);
    }
/*
    public function testResolveBadDTO(): void
    {
        $this->expectException(ValidationException::class);

        $request = $this->createBadUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'createUser', $request);

        $this->listener->onKernelController($event);

        $param = $this->getMockBuilder(ArgumentMetadata::class)->disableOriginalConstructor()->getMock();

        $user = $this->resolver->resolve($request, $param);

        self::assertInstanceOf(User::class, $user->current());
    }*/
}
