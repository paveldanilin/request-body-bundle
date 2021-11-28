<?php

namespace Pada\RequestBodyBundle\Cache;

use Pada\Reflection\Scanner\ScannerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class RequestBodyWarmer implements CacheWarmerInterface
{
    private ScannerInterface $scanner;
    private ParameterBagInterface $parameterBag;

    public function __construct(ScannerInterface $scanner, ParameterBagInterface $parameterBag)
    {
        $this->scanner = $scanner;
        $this->parameterBag = $parameterBag;
    }

    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        print '>>' . $cacheDir . "]]\n";

        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $this->scanner->in($projectDir . DIRECTORY_SEPARATOR . 'src');

        return [];
    }
}
