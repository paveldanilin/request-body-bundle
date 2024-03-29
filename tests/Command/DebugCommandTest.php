<?php


namespace Pada\RequestBodyBundle\Tests\Command;

use Pada\RequestBodyBundle\Command\DebugCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DebugCommandTest extends KernelTestCase
{
    public function testCommand(): void
    {
        $kernel = static::createKernel();
        $app = new Application($kernel);

        /** @var DebugCommand $cmd */
        $cmd = $app->find('debug:request-body');
        $cmd->setScanDir(\dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures');
        $cmdTester = new CommandTester($cmd);
        $cmdTester->execute([]);

        $output = $cmdTester->getDisplay();

        static::assertNotEmpty($output);
    }
}
