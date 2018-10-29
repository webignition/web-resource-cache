<?php

namespace App\Tests\Functional\Services;

use App\Resque\Job\RetrieveResourceJob;
use App\Services\ResqueQueueService;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Mockery\Mock;
use Psr\Log\LoggerInterface;
use ResqueBundle\Resque\Resque;

class ResqueQueueServiceTest extends AbstractFunctionalTestCase
{
    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->resqueQueueService = self::$container->get(ResqueQueueService::class);
    }

    public function testEnqueueSuccess()
    {
        $queueName = RetrieveResourceJob::QUEUE_NAME;

        $this->clearRedis();
        $this->assertTrue($this->resqueQueueService->isEmpty($queueName));

        $this->resqueQueueService->enqueue(new RetrieveResourceJob(['id' => 'example-id']));
        $this->assertFalse($this->resqueQueueService->isEmpty($queueName));
    }

    public function testEnqueueFailure()
    {
        $credisException = \Mockery::mock(\CredisException::class);

        /* @var Mock|Resque $resque */
        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('enqueue')
            ->andThrow($credisException);

        /* @var LoggerInterface|Mock $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('warning')
            ->with('ResqueQueueService::enqueue: Redis error []');

        $resqueQueueService = $this->createQueueService($resque, $logger);
        $this->assertNull($resqueQueueService->enqueue(new RetrieveResourceJob(['id' => 'example-id'])));
    }

    public function testIsEmptyFailure()
    {
        $queue = RetrieveResourceJob::QUEUE_NAME;
        $credisException = \Mockery::mock(\CredisException::class);

        /* @var Mock|Resque $resque */
        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('getQueue')
            ->with($queue)
            ->andThrow($credisException);

        /* @var LoggerInterface|Mock $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('warning')
            ->with('ResqueQueueService::isEmpty: Redis error []');

        $resqueQueueService = $this->createQueueService($resque, $logger);
        $this->assertFalse($resqueQueueService->isEmpty($queue));
    }

    private function createQueueService(Resque $resque, LoggerInterface $logger): ResqueQueueService
    {
        return new ResqueQueueService($resque, $logger);
    }
}
