<?php


namespace paveldanilin\RequestBodyBundle\Tests;


use paveldanilin\RequestBodyBundle\RequestBodyBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new RequestBodyBundle()
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // TODO: Implement registerContainerConfiguration() method.
    }
}
