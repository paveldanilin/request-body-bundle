<?php


namespace paveldanilin\RequestBodyBundle\Tests\Service;


use Doctrine\Common\Annotations\AnnotationReader;
use paveldanilin\RequestBodyBundle\Service\AnnotationScanner;
use paveldanilin\RequestBodyBundle\Service\AnnotationScannerInterface;
use PHPUnit\Framework\TestCase;

class AnnotationScannerTest extends TestCase
{
    private string $scanDir;
    private AnnotationScannerInterface $annotationScanner;

    protected function setUp(): void
    {
        $this->scanDir = \dirname(__DIR__) . '/Fixtures';
        $this->annotationScanner = new AnnotationScanner(new AnnotationReader());
    }

    public function testScanIn(): void
    {
        $c = 0;
        foreach ($this->annotationScanner->in($this->scanDir) as $classAnnotation) {
            $c++;
        }

        self::assertEquals(2, $c);
    }
}
