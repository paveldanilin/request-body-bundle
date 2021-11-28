<?php


namespace Pada\RequestBodyBundle\Tests\Service;


use Pada\Reflection\Scanner\Scanner;
use Pada\Reflection\Scanner\ScannerInterface;
use PHPUnit\Framework\TestCase;

class AnnotationScannerTest extends TestCase
{
    private string $scanDir;
    private ScannerInterface $annotationScanner;

    protected function setUp(): void
    {
        $this->scanDir = \dirname(__DIR__) . '/Fixtures';
        $this->annotationScanner = new Scanner();
    }

    public function testScanIn(): void
    {
        $c = 0;
        foreach ($this->annotationScanner->in($this->scanDir) as $classInfo) {
            $c++;
        }

        self::assertEquals(2, $c);
    }
}
