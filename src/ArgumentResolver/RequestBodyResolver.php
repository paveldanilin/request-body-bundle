<?php


namespace Pada\RequestBodyBundle\ArgumentResolver;

use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Pada\RequestBodyBundle\Service\RequestBodyServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;


class RequestBodyResolver implements ArgumentValueResolverInterface
{
    private RequestBodyServiceInterface $requestBodyService;

    public function __construct(RequestBodyServiceInterface $requestBodyService)
    {
        $this->requestBodyService = $requestBodyService;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if (false === $request->attributes->has(RequestBody::REQUEST_ATTRIBUTE)) {
            return false;
        }

        /** @var RequestBody $requestBody */
        $requestBody = $request->attributes->get(RequestBody::REQUEST_ATTRIBUTE);

        return $argument->getName() === $requestBody->param && $argument->getType() === $requestBody->type;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        /** @var RequestBody $requestBody */
        $requestBody = $request->attributes->get(RequestBody::REQUEST_ATTRIBUTE);

        $target = $this->requestBodyService->deserializeRequest($request, $requestBody);

        $this->requestBodyService->validateTarget($target, $requestBody);

        yield $target;
    }
}
