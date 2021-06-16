<?php


namespace paveldanilin\RequestBodyBundle\Service;

interface AnnotationScannerInterface
{
    /**
     * @param string $dir
     * @return \Generator
     */
    public function in(string $dir);
}
