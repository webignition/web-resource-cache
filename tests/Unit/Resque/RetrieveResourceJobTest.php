<?php

namespace App\Tests\Unit\Resque;

use App\Command\RetrieveResourceCommand;
use App\Model\RetrieveRequest;
use App\Resque\Job\RetrieveResourceJob;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ResqueBundle\Resque\ContainerAwareJob;
use webignition\HttpHeaders\Headers;

class RetrieveResourceJobTest extends AbstractJobTest
{
    public function testCreate()
    {
        $requestHash = 'request_hash';
        $url = 'http://example.com/';
        $headersArray = [];
        $retryCount = 2;

        $retrieveRequest = new RetrieveRequest($requestHash, $url, new Headers($headersArray), $retryCount);
        $retrieveResourceJob = new RetrieveResourceJob([
            'request-json' => json_encode($retrieveRequest),
        ]);

        $this->assertEquals(RetrieveResourceJob::QUEUE_NAME, $retrieveResourceJob->queue);
        $this->assertEquals(
            [
                'request-json' => json_encode([
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => $requestHash,
                    RetrieveRequest::JSON_KEY_URL => $url,
                    RetrieveRequest::JSON_KEY_HEADERS => $headersArray,
                    RetrieveRequest::JSON_KEY_RETRY_COUNT => $retryCount,
                ]),
            ],
            $retrieveResourceJob->args
        );
    }

    /**
     * @throws \Exception
     */
    public function testRunSuccess()
    {
        $requestHash = 'request_hash';
        $url = 'http://example.com/';
        $headersArray = [];
        $retryCount = 2;

        $retrieveRequest = new RetrieveRequest($requestHash, $url, new Headers($headersArray), $retryCount);
        $encodedRetrieveRequest = json_encode($retrieveRequest);

        $retrieveResourceJob = new RetrieveResourceJob([
            'request-json' => $encodedRetrieveRequest,
        ]);

        $retrieveResourceCommand = $this->createCommand(
            RetrieveResourceCommand::class,
            [
                'request-json' => $encodedRetrieveRequest,
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
        $requestHash = 'request_hash';
        $url = 'http://example.com/';
        $headersArray = [];
        $retryCount = 2;

        $retrieveRequest = new RetrieveRequest($requestHash, $url, new Headers($headersArray), $retryCount);
        $encodedRetrieveRequest = json_encode($retrieveRequest);

        $retrieveResourceJob = new RetrieveResourceJob([
            'request-json' => $encodedRetrieveRequest,
        ]);

        $retrieveResourceCommand = $this->createCommand(
            RetrieveResourceCommand::class,
            [
                'request-json' => $encodedRetrieveRequest,
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
