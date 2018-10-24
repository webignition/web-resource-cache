<?php

namespace App\Tests\Unit\Resque;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractJobTest extends \PHPUnit\Framework\TestCase
{
    protected function createKernel(ContainerInterface $container)
    {
        $kernel = \Mockery::mock(KernelInterface::class);
        $kernel
            ->shouldReceive('getContainer')
            ->andReturn($container);

        return $kernel;
    }

    protected function createCommand(string $commandClass, array $expectedInputParameters, int $returnCode)
    {
        $retrieveResourceCommand = \Mockery::mock($commandClass);
        $retrieveResourceCommand
            ->shouldReceive('run')
            ->withArgs(function (ArrayInput $input, BufferedOutput $output) use ($expectedInputParameters) {

                $reflector = new \ReflectionClass(ArrayInput::class);
                $property = $reflector->getProperty('parameters');
                $property->setAccessible('true');
                $property->getValue($input);

                $this->assertEquals($expectedInputParameters, $property->getValue($input));
                $this->assertInstanceOf(BufferedOutput::class, $output);

                return true;
            })
            ->andReturn($returnCode);

        return $retrieveResourceCommand;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
