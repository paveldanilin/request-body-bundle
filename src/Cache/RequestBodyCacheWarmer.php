<?php

namespace Pada\RequestBodyBundle\Cache;

use Pada\Reflection\Scanner\ClassInfo;
use Pada\Reflection\Scanner\ScannerInterface;
use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Pada\RequestBodyBundle\Util;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class RequestBodyCacheWarmer implements CacheWarmerInterface
{
    private ScannerInterface $scanner;
    private CacheItemPoolInterface $cacheSystem;
    private bool $throwException = true;
    private string $scanDir;

    public function __construct(string $scanDir, ScannerInterface $scanner, CacheItemPoolInterface $cacheSystem)
    {
        $this->scanDir = $scanDir;
        $this->scanner = $scanner;
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

    /** @phpstan-ignore-next-line
     * @throws \Exception
     */
    public function warmUp($cacheDir)
    {
        /** @var ClassInfo $classInfo */
        foreach ($this->scanner->in($this->scanDir) as $classInfo) {
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

        if (false === \class_exists($requestBody->type)) {
            throw new \LogicException("Type not found `$requestBody->type`.");
        }

        $cachedItem = $this->cacheSystem->getItem(Util::getCacheKey($classInfo->getReflection()->getName(), $methodName));
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
