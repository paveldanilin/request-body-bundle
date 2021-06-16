<?php


namespace paveldanilin\RequestBodyBundle;


final class ClassAnnotationInfo
{
    private string $namespace;
    private string $class;
    private string $filename;
    // Class level annotations
    private \ArrayIterator $annotations;
    // { methodName: [] }
    private \ArrayIterator $methods;

    public function __construct(string $filename, string $namespace, string $class, \ArrayIterator $annotations, \ArrayIterator $methods)
    {
        $this->filename = $filename;
        $this->namespace = $namespace;
        $this->class = $class;
        $this->annotations = $annotations;
        $this->methods = $methods;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getAnnotations(): \ArrayIterator
    {
        return $this->annotations;
    }

    public function getMethods(): \ArrayIterator
    {
        return $this->methods;
    }

    public function getMethod(string $methodName): \ArrayIterator
    {
        if ($this->methods->offsetExists($methodName)) {
            return new \ArrayIterator($this->methods->offsetGet($methodName));
        }
        return new \ArrayIterator([]);
    }
}
