<?php

namespace Pada\RequestBodyBundle\Cache;

use Pada\Reflection\Scanner\ClassInfo;
use Pada\Reflection\Scanner\ScannerInterface;
use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class RequestBodyCacheWarmer implements CacheWarmerInterface
{
    private ScannerInterface $scanner;
    private ParameterBagInterface $parameterBag;
    private CacheItemPoolInterface $cacheSystem;
    private bool $throwException = true;

    public function __construct(ScannerInterface $scanner, ParameterBagInterface $parameterBag, CacheItemPoolInterface $cacheSystem)
    {
        $this->scanner = $scanner;
        $this->parameterBag = $parameterBag;
        $this->cacheSystem = $cacheSystem;
    }

    public function throwException(bool $throwException): void
    {
        $this->throwException = $throwException;
    }

    public function isOptional()
    {
        return true;
    }

    /** @phpstan-ignore-next-line */
    public function warmUp($cacheDir)
    {
        $scanDir = $this->parameterBag->get('kernel.project_dir');
        if (\is_dir($scanDir . DIRECTORY_SEPARATOR . 'src')) {
            $scanDir .= DIRECTORY_SEPARATOR . 'src';
        }

        /** @var ClassInfo $classInfo */
        foreach ($this->scanner->in($scanDir) as $classInfo) {
            foreach ($classInfo->getMethodNames() as $methodName) {
                try {
                    $this->doWarmUp($classInfo, $methodName);
                } catch (\Exception $exception) {
                    if ($this->throwException) {
                        throw $exception;
                    }
                }
            }
        }

        return [];
    }

    private function getMethodAnnotation(ClassInfo $classInfo, string $method): ?RequestBody
    {
        foreach ($classInfo->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof RequestBody) {
                return $annotation;
            }
        }
        return null;
    }

    private function doWarmUp(ClassInfo $classInfo, string $methodName): void
    {
        $reflectionMethod = $classInfo->getReflection()->getMethod($methodName);

        $requestBody = $this->getMethodAnnotation($classInfo, $methodName);
        if (null === $requestBody) {
            return;
        }

        // Param
        if (empty($requestBody->param)) {
            $requestBody->param = $this->getParam($reflectionMethod);
        }

        // Type
        if (empty($requestBody->type)) {
            $requestBody->type = $this->getParameterType($reflectionMethod->getParameters(), $requestBody->param);
        }

        $key = \md5($classInfo->getReflection()->getName() . '_' . $methodName);

        $cachedItem = $this->cacheSystem->getItem($key);
        $cachedItem->set($requestBody);
        $this->cacheSystem->save($cachedItem);
    }

    private function getParam(\ReflectionMethod $method): string
    {
        $numOfParams = $method->getNumberOfParameters();

        if (0 === $numOfParams) {
            throw new \LogicException('Could not autodetect parameter for body mapping. The method does not have parameters.');
        }

        if (1 < $numOfParams) {
            throw new \LogicException('Could not autodetect parameter for body mapping. The method has too many parameters.');
        }

        return $method->getParameters()[0]->getName();
    }

    /**
     * @param array<\ReflectionParameter> $parameters
     * @param string $parameterName
     * @return string
     * @throws \LogicException
     */
    private function getParameterType(array $parameters, string $parameterName): string
    {
        foreach ($parameters as $parameter) {
            if ($parameter->getName() === $parameterName) {
                return $this->getType($parameter);
            }
        }

        throw new \LogicException(
            "Parameter `$parameterName` not found."
        );
    }

    private function getType(\ReflectionParameter $parameter): string
    {
        if (false === $parameter->hasType() || null === $parameter->getType()) {
            throw new \LogicException(
                "Parameter `{$parameter->name}` does not have type hint."
            );
        }

        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        throw new \LogicException(
            "Parameter `{$parameter->name}` does not have type hint."
        );
    }
}
