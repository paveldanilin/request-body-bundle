<?php


namespace paveldanilin\RequestBodyBundle\ArgumentResolver;

use paveldanilin\RequestBodyBundle\Annotation\RequestBody;
use paveldanilin\RequestBodyBundle\Exception\DeserializationException;
use paveldanilin\RequestBodyBundle\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class RequestBodyResolver implements ArgumentValueResolverInterface
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var ValidatorInterface */
    private $validator;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        // No annotation
        if (false === $request->attributes->has(RequestBody::REQUEST_ATTRIBUTE)) {
            return false;
        }

        // We support only PUT, PATCH, POST  HTTP methods
        if (false === \in_array($request->getMethod(), ['PUT', 'PATCH', 'POST'])) {
            return false;
        }

        /** @var RequestBody $requestBody */
        $requestBody = $request->attributes->get(RequestBody::REQUEST_ATTRIBUTE);

        return $argument->getName() === $requestBody->param;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        /** @var RequestBody $requestBody */
        $requestBody = $request->attributes->get(RequestBody::REQUEST_ATTRIBUTE);

        // Body is empty
        if (empty($request->getContent())) {
            throw new BadRequestHttpException('The request body is empty');
        }

        // RequestContent -> Symfony.deserialize -> ArgumentType
        try {
            $target = $this->serializer->deserialize(
                $request->getContent(),
                $requestBody->input,
                $requestBody->getSerializationFormat(),
                $requestBody->deserializationContext
            );
        } catch (\Throwable $throwable) {
            throw new DeserializationException(
                $requestBody->deserializationError ?? 'Could not deserialize request body',
                $throwable
            );
        }

        if(false === empty($requestBody->validationGroups)) {
            if (1 === \count($requestBody->validationGroups) && 'all' === \strtolower($requestBody->validationGroups[0])) {
                // validationGroups={"all"}
                $validationErrors = $this->validator->validate($target);
            } else {
                $validationErrors = $this->validator->validate($target, null, $requestBody->validationGroups);
            }
            if ($validationErrors->count() > 0) {
                throw new ValidationException($requestBody->validationError, $validationErrors);
            }
        }

        if ($argument->getType() !== $requestBody->input) {
            throw new \InvalidArgumentException("Type miss match for argument `{$argument->getName()}`");
        }

        yield $target;
    }
}
