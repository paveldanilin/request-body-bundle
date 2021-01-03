<?php


namespace paveldanilin\RequestBodyBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class DeserializationException extends BadRequestHttpException
{
    public function __construct(string $message = 'Could not deserialize request body', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
