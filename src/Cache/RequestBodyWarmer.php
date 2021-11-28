<?php

namespace Pada\RequestBodyBundle\Cache;

use Pada\Reflection\Scanner\ClassInfo;
use Pada\Reflection\Scanner\ScannerInterface;
use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class RequestBodyWarmer implements CacheWarmerInterface
{
    private ScannerInterface $scanner;
    private ParameterBagInterface $parameterBag;
    private CacheItemPoolInterface $cacheSystem;

    public function __construct(ScannerInterface $scanner, ParameterBagInterface $parameterBag, CacheItemPoolInterface $cacheSystem)
    {
        $this->scanner = $scanner;
        $this->parameterBag = $parameterBag;
        $this->cacheSystem = $cacheSystem;
    }

    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');

        /** @var ClassInfo $classInfo */
        foreach ($this->scanner->in($projectDir . DIRECTORY_SEPARATOR . 'src') as $classInfo) {
            foreach ($classInfo->getMethodNames() as $methodName) {
                foreach ($classInfo->getMethodAnnotations($methodName) as $annotation) {
                    if ($annotation instanceof RequestBody) {
                        $key = \md5($classInfo->getReflection()->getName() . '_' . $methodName);
                        $reflectionMethod = new \ReflectionMethod($classInfo->getReflection()->getName(), $methodName);
                        $cacheItem = $this->cacheSystem->getItem($key);
                        $cacheItem->set($reflectionMethod);
                        $this->cacheSystem->save($cacheItem);
                    }
                }
            }
        }

        return [];
    }
}
