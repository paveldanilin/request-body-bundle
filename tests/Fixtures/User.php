<?php


namespace paveldanilin\RequestBodyBundle\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

class User
{
    /**
     * @Assert\NotBlank(allowNull=false)
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $name;
}
