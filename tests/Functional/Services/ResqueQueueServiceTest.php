<?php

namespace App\Tests\Functional\Services;

use App\Resque\Job\RetrieveResourceJob;
use App\Services\ResqueQueueService;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Mockery\Mock;
use Psr\Log\LoggerInterface;
use ResqueBundle\Resque\Job;
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

    /**
     * @dataProvider containsSuccessDataProvider
     *
     * @param Job[] $jobs
     * @param string $queue
     * @param array $args
     * @param bool $expectedContains
     */
    public function testContainsSuccess(array $jobs, $queue, $args, $expectedContains)
    {
        $this->clearRedis();

        foreach ($jobs as $job) {
            $this->resqueQueueService->enqueue($job);
        }

        $this->assertEquals($expectedContains, $this->resqueQueueService->contains($queue, $args));
    }

    /**
     * @return array
     */
    public function containsSuccessDataProvider()
    {
        return [
            'empty queue' => [
                'jobs' => [],
                'queue' => RetrieveResourceJob::QUEUE_NAME,
                'args' => [],
                'expectedContains' => false,
            ],
            'non-matching args (no keys)' => [
                'jobs' => [
                    new RetrieveResourceJob([
                        'id' => 'example-id',
                    ]),
                ],
                'queue' => RetrieveResourceJob::QUEUE_NAME,
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedContains' => false,
            ],
            'non-matching args (no matching values)' => [
                'jobs' => [
                    new RetrieveResourceJob([
                        'id' => 'example-id',
                    ]),
                ],
                'queue' => RetrieveResourceJob::QUEUE_NAME,
                'args' => [
                    'id' => 'non-matching-id',
                ],
                'expectedContains' => false,
            ],
            'matching args' => [
                'jobs' => [
                    new RetrieveResourceJob([
                        'id' => 'example-id',
                    ]),
                ],
                'queue' => RetrieveResourceJob::QUEUE_NAME,
                'args' => [
                    'id' => 'example-id',
                ],
                'expectedContains' => true,
            ],
        ];
    }

    public function testContainsFailure()
    {
        $credisException = \Mockery::mock(\CredisException::class);

        $queue = 'tasks-notify';

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
            ->with('ResqueQueueService::contains: Redis error []');

        $resqueQueueService = $this->createQueueService($resque, $logger);

        $this->assertFalse($resqueQueueService->contains($queue));
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
