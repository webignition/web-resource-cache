<?php

namespace App\Tests\Unit\Resque;

use App\Command\RetrieveResourceCommand;
use App\Resque\Job\RetrieveResourceJob;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ResqueBundle\Resque\ContainerAwareJob;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class RetrieveResourceJobTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $requestHash = 'example-hash';
        $retrieveResourceJob = new RetrieveResourceJob([
            'request-hash' => $requestHash,
        ]);

        $this->assertEquals(RetrieveResourceJob::QUEUE_NAME, $retrieveResourceJob->queue);
        $this->assertEquals(['request-hash' => $requestHash], $retrieveResourceJob->args);
    }

    /**
     * @throws \Exception
     */
    public function testRunSuccess()
    {
        $requestHash = 'example-hash';
        $retrieveResourceJob = new RetrieveResourceJob([
            'request-hash' => $requestHash,
        ]);

        $retrieveResourceCommand = $this->createRetrieveResourceCommand(
            $requestHash,
            RetrieveResourceCommand::RETURN_CODE_OK
        );

        $container = \Mockery::mock(ContainerInterface::class);
        $container
            ->shouldReceive('get')
            ->with(RetrieveResourceCommand::class)
            ->andReturn($retrieveResourceCommand);

        $reflector = new \ReflectionClass(ContainerAwareJob::class);
        $property = $reflector->getProperty('kernel');
        $property->setAccessible(true);
        $property->setValue($retrieveResourceJob, $this->createKernel($container));

        $this->assertTrue($retrieveResourceJob->run([]));
    }

    /**
     * @throws \Exception
     */
    public function testRunFailure()
    {
        $requestHash = 'example-hash';
        $retrieveResourceJob = new RetrieveResourceJob([
            'request-hash' => $requestHash,
        ]);

        $retrieveResourceCommand = $this->createRetrieveResourceCommand(
            $requestHash,
            RetrieveResourceCommand::RETURN_CODE_RETRIEVE_REQUEST_NOT_FOUND
        );

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error');

        $container = \Mockery::mock(ContainerInterface::class);
        $container
            ->shouldReceive('get')
            ->with(RetrieveResourceCommand::class)
            ->andReturn($retrieveResourceCommand);

        $container
            ->shouldReceive('get')
            ->with('logger')
            ->andReturn($logger);

        $reflector = new \ReflectionClass(ContainerAwareJob::class);
        $property = $reflector->getProperty('kernel');
        $property->setAccessible(true);
        $property->setValue($retrieveResourceJob, $this->createKernel($container));

        $this->assertTrue($retrieveResourceJob->run([]));
    }

    private function createRetrieveResourceCommand(string $requestHash, int $returnCode)
    {
        $retrieveResourceCommand = \Mockery::mock(RetrieveResourceCommand::class);
        $retrieveResourceCommand
            ->shouldReceive('run')
            ->withArgs(function (ArrayInput $input, BufferedOutput $output) use ($requestHash) {

                $reflector = new \ReflectionClass(ArrayInput::class);
                $property = $reflector->getProperty('parameters');
                $property->setAccessible('true');
                $property->getValue($input);

                $this->assertEquals(
                    [
                        'request-hash' => $requestHash,
                    ],
                    $property->getValue($input)
                );

                return true;
            })
            ->andReturn($returnCode);

        return $retrieveResourceCommand;
    }

    private function createKernel(ContainerInterface $container)
    {
        $kernel = \Mockery::mock(KernelInterface::class);
        $kernel
            ->shouldReceive('getContainer')
            ->andReturn($container);

        return $kernel;
    }
}
