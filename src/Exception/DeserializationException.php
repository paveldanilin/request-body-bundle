<?php


namespace Pada\RequestBodyBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class DeserializationException extends BadRequestHttpException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
