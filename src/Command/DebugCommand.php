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
        $table->setHeaders(['Class', 'Method', 'Bind Param', 'Validation Context']);
        $rows = [];

        /** @var ClassAnnotationInfo $classAnnotation */
        foreach ($this->annotationScanner->in($this->scanDir) as $classAnnotation) {

            foreach ($classAnnotation->getMethods() as $methodName => $methodAnnotations) {
              foreach ($methodAnnotations as $methodAnnotation) {
                  if ($methodAnnotation instanceof RequestBody) {
                      $rows[] = [$classAnnotation->getClass(), $methodName, $methodAnnotation->param, \implode(',', $methodAnnotation->validationGroups)];
                  }
              }
            }

            $rows[] = new TableSeparator();
        }

        $table->setRows($rows)->render();

        return 0;
    }
}
