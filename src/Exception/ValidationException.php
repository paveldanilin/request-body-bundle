<?php


namespace paveldanilin\RequestBodyBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;


class ValidationException extends BadRequestHttpException
{
    /** @var  ConstraintViolationListInterface */
    private $constraintViolationList;

    public function __construct(ConstraintViolationListInterface $constraintViolationList, string $message = 'Transfer data object is not valid')
    {
        parent::__construct($message);
        $this->constraintViolationList = $constraintViolationList;
    }

    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
    }
}
