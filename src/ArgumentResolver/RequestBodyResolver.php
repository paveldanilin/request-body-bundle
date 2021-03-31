<?php


namespace paveldanilin\RequestBodyBundle\ArgumentResolver;

use paveldanilin\RequestBodyBundle\Controller\Annotation\RequestBody;
use paveldanilin\RequestBodyBundle\Exception\DeserializationException;
use paveldanilin\RequestBodyBundle\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class RequestBodyResolver implements ArgumentValueResolverInterface
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;


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

        $target = $this->deserialize($request, $requestBody);

        $this->validate($target, $requestBody);

        yield $target;
    }

    /**
     * @param Request $request
     * @param RequestBody $requestBody
     * @return mixed
     */
    private function deserialize(Request $request, RequestBody $requestBody)
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
    private function validate($target, RequestBody $requestBody): void
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
}
