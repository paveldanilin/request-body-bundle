<?php


namespace paveldanilin\RequestBodyBundle\Command;

use paveldanilin\RequestBodyBundle\ClassAnnotationInfo;
use paveldanilin\RequestBodyBundle\Controller\Annotation\RequestBody;
use paveldanilin\RequestBodyBundle\Service\AnnotationScannerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugCommand extends Command
{
    protected static $defaultName = 'request-body:debug';

    private AnnotationScannerInterface $annotationScanner;
    private string $scanDir;

    public function setScanDir(string $scanDir): void
    {
        $this->scanDir = $scanDir;
    }

    public function setAnnotationScanner(AnnotationScannerInterface $annotationScanner): void
    {
        $this->annotationScanner = $annotationScanner;
    }

    protected function configure(): void
    {
        $this->setDescription('Shows controllers and actions that use the @RequestBody annotation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Class', 'Method', 'Bind Param', 'Param Type', 'Validation Context']);
        $rows = [];

        /** @var ClassAnnotationInfo $classAnnotation */
        foreach ($this->annotationScanner->in($this->scanDir) as $classAnnotation) {

            foreach ($classAnnotation->getMethods() as $methodName => $methodAnnotations) {
              foreach ($methodAnnotations as $methodAnnotation) {
                  if ($methodAnnotation instanceof RequestBody) {

                      $reflectionMethod = new \ReflectionMethod($classAnnotation->getNamespace() . '\\' . $classAnnotation->getClass(), $methodName);
                      $param = null;
                      $paramHint = '';
                      foreach ($reflectionMethod->getParameters() as $parameter) {
                          if ($parameter->getName() === $methodAnnotation->param) {
                              $param = $parameter;
                              break;
                          }
                      }
                      if (null !== $param && $param->hasType() && $param->getType() instanceof \ReflectionNamedType) {
                          $paramHint = $param->getType()->getName();
                      }

                      $rows[] = [
                          $classAnnotation->getClass(),
                          $methodName,
                          $methodAnnotation->param,
                          $paramHint,
                          \implode(',', $methodAnnotation->validationGroups)
                      ];
                  }
              }
            }
        }

        $table->setRows($rows)->render();

        return 0;
    }
}
