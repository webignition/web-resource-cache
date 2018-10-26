<?php

namespace App\Tests\Unit\Resque;

use App\Command\SendResponseCommand;
use App\Model\Response\AbstractResponse;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Resque\Job\SendResponseJob;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ResqueBundle\Resque\ContainerAwareJob;

class SendResponseJobTest extends AbstractJobTest
{
    /**
     * @dataProvider responseDataProvider
     *
     * @param AbstractResponse $response
     */
    public function testCreate(AbstractResponse $response)
    {
        $responseJson = json_encode($response);

        $sendResponseJob = new SendResponseJob([
            'response-json' => json_encode($response),
        ]);

        $this->assertEquals(SendResponseJob::QUEUE_NAME, $sendResponseJob->queue);
        $this->assertEquals(['response-json' => $responseJson], $sendResponseJob->args);
    }

    /**
     * @dataProvider responseDataProvider
     *
     * @param AbstractResponse $response
     *
     * @throws \Exception
     */
    public function testRunSuccess(AbstractResponse $response)
    {
        $responseJson = json_encode($response);

        $sendResponseJob = new SendResponseJob([
            'response-json' => $responseJson,
        ]);

        $sendResponseCommand = $this->createCommand(
            SendResponseCommand::class,
            [
                'response-json' => $responseJson,
            ],
            SendResponseCommand::RETURN_CODE_OK
        );

        $container = \Mockery::mock(ContainerInterface::class);
        $container
            ->shouldReceive('get')
            ->with(SendResponseCommand::class)
            ->andReturn($sendResponseCommand);

        $reflector = new \ReflectionClass(ContainerAwareJob::class);
        $property = $reflector->getProperty('kernel');
        $property->setAccessible(true);
        $property->setValue($sendResponseJob, $this->createKernel($container));

        $this->assertTrue($sendResponseJob->run([]));
    }

    /**
     * @return array
     */
    public function responseDataProvider(): array
    {
        return [
            'unknown failure response' => [
                'response' => new UnknownFailureResponse('request_hash_1'),
            ],
            'connection failure response' => [
                'response' => new KnownFailureResponse('request_hash_2', KnownFailureResponse::TYPE_CONNECTION, 28),
            ],
            'http failure response' => [
                'response' => new KnownFailureResponse('request_hash_3', KnownFailureResponse::TYPE_HTTP, 404),
            ],
            'success response' => [
                'response' => new SuccessResponse('request_hash_4'),
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testRunFailure()
    {
        $response = new UnknownFailureResponse('request_hash_1');

        $sendResponseJob = new SendResponseJob([
            'response-json' => json_encode($response),
        ]);

        $sendResponseCommand = $this->createCommand(
            SendResponseCommand::class,
            [
                'response-json' => '{"request_id":"request_hash_1","status":"failed","failure_type":"unknown"}',
            ],
            1
        );

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error');

        $container = \Mockery::mock(ContainerInterface::class);
        $container
            ->shouldReceive('get')
            ->with(SendResponseCommand::class)
            ->andReturn($sendResponseCommand);

        $container
            ->shouldReceive('get')
            ->with('logger')
            ->andReturn($logger);

        $reflector = new \ReflectionClass(ContainerAwareJob::class);
        $property = $reflector->getProperty('kernel');
        $property->setAccessible(true);
        $property->setValue($sendResponseJob, $this->createKernel($container));

        $this->assertTrue($sendResponseJob->run([]));
    }
}
