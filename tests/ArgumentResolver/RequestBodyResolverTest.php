<?php


namespace paveldanilin\RequestBodyBundle\Tests\ArgumentResolver;


use paveldanilin\RequestBodyBundle\Controller\Annotation\RequestBody;
use paveldanilin\RequestBodyBundle\Exception\ValidationException;
use paveldanilin\RequestBodyBundle\Tests\Fixtures\TestUserController;
use paveldanilin\RequestBodyBundle\Tests\Fixtures\User;
use paveldanilin\RequestBodyBundle\Tests\RequestBodyTestCase;
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

    public function testResolveBadDTO(): void
    {
        $this->expectException(ValidationException::class);

        $request = $this->createBadUserJsonRequest();

        $event = $this->createControllerEvent(new TestUserController(), 'createUser', $request);

        $this->listener->onKernelController($event);

        $param = $this->getMockBuilder(ArgumentMetadata::class)->disableOriginalConstructor()->getMock();

        $user = $this->resolver->resolve($request, $param);

        self::assertInstanceOf(User::class, $user->current());
    }
}
