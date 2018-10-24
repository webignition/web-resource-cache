<?php

namespace App\Tests\Unit\Resque;

use App\Command\RetrieveResourceCommand;
use App\Resque\Job\RetrieveResourceJob;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ResqueBundle\Resque\ContainerAwareJob;

class RetrieveResourceJobTest extends AbstractJobTest
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

        $retrieveResourceCommand = $this->createCommand(
            RetrieveResourceCommand::class,
            [
                'request-hash' => $requestHash,
            ],
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

        $retrieveResourceCommand = $this->createCommand(
            RetrieveResourceCommand::class,
            [
                'request-hash' => $requestHash,
            ],
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
}
