<?php


namespace paveldanilin\RequestBodyBundle\Command;

use paveldanilin\RequestBodyBundle\ClassAnnotationInfo;
use paveldanilin\RequestBodyBundle\Service\AnnotationScannerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowUsageCommand extends Command
{
    protected static $defaultName = 'rb:show';

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
        /** @var ClassAnnotationInfo $classAnnotation */
        foreach ($this->annotationScanner->in($this->scanDir) as $classAnnotation) {
            $output->writeln($classAnnotation->getFilename());
            $output->writeln($classAnnotation->getClass());
        }

        return 0;
    }
}
