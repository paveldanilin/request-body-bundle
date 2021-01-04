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

        $target = $this->deserialize($request, $requestBody);

        $this->validate($target, $requestBody);

        if ($argument->getType() !== $requestBody->type) {
            throw new \InvalidArgumentException("Type miss match for argument `{$argument->getName()}`.");
        }

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
                    'Could not deserialize request body [type]=%s [format]=%s',
                    $requestBody->type,
                    $requestBody->getSerializationFormat()
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
