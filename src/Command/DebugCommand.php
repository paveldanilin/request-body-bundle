<?php


namespace Pada\RequestBodyBundle\Command;

use Pada\Reflection\Scanner\ClassInfo;
use Pada\Reflection\Scanner\ScannerInterface;
use Pada\RequestBodyBundle\Controller\Annotation\RequestBody;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugCommand extends Command
{
    protected static $defaultName = 'debug:request-body';

    private ScannerInterface $reflectionScanner;
    private string $scanDir;

    public function setScanDir(string $scanDir): void
    {
        $this->scanDir = $scanDir;
    }

    public function setReflectionScanner(ScannerInterface $reflectionScanner): void
    {
        $this->reflectionScanner = $reflectionScanner;
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

        /** @var ClassInfo $classInfo */
        foreach ($this->reflectionScanner->in($this->scanDir) as $classInfo) {

            foreach ($classInfo->getMethodNames() as $methodName) {
              foreach ($classInfo->getMethodAnnotations($methodName) as $methodAnnotation) {
                  if (!($methodAnnotation instanceof RequestBody)) {
                      continue;
                  }

                  $reflectionMethod = $classInfo->getReflection()->getMethod($methodName);
                  $reflectionParam = null;
                  $paramTypeHint = '';
                  [$paramError, $paramName] = $this->getBindParamInfo($methodAnnotation->param, $reflectionMethod);

                  foreach ($reflectionMethod->getParameters() as $parameter) {
                      if ($parameter->getName() === $paramName) {
                          $reflectionParam = $parameter;
                          break;
                      }
                  }

                  if (null === $reflectionParam && 0 === $paramError) {
                      $paramError = 1;
                      $paramName = "Method does not have such parameter '$paramName'";
                  }

                  if (null !== $reflectionParam) {
                      if ($reflectionParam->hasType() && $reflectionParam->getType() instanceof \ReflectionNamedType) {
                          $paramTypeHint = $reflectionParam->getType()->getName();
                      } else if (0 === $paramError) {
                          $paramError = 1;
                          $paramName = "The '$paramName' parameter does not have a type hint";
                      } else {
                          $paramName .= "; Parameter does not have a type hint";
                      }
                  }

                  $rows[] = [
                      $classInfo->getReflection()->getName(),
                      $methodName,
                      $this->wrapText($paramError, $paramName),
                      $paramTypeHint,
                      \implode(',', $methodAnnotation->validationGroups),
                  ];
              }
            }
        }

        $table->setRows($rows)->render();

        return 0;
    }

    private function wrapText(int $error, string $text): string
    {
        return ($error === 1 ? '<error>' : '<info>') . $text . ($error === 1 ? '</error>' : '</info>');
    }

    private function getBindParamInfo(string $annotationParamName, \ReflectionMethod $method): array
    {
        $error = 0;
        if (empty($annotationParamName)) {
            $numOfParams = $method->getNumberOfParameters();
            if (0 === $numOfParams) {
                $error = 1;
                $bindParam = 'Could not autodetect parameter for body mapping. The method does not have parameters.';
            } elseif (1 < $numOfParams) {
                $error = 1;
                $bindParam = 'Could not autodetect parameter for body mapping. The method has too many parameters.';
            } else {
                $bindParam = $method->getParameters()[0]->getName();
            }
        } else {
            $bindParam = $annotationParamName;
        }
        return [$error, $bindParam];
    }
}
