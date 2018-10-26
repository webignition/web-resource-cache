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
     * @param Job[] $jobsToEnqueue
     * @param Job $job
     * @param bool $expectedContains
     */
    public function testContainsSuccess(array $jobsToEnqueue, Job $job, $expectedContains)
    {
        $this->clearRedis();

        foreach ($jobsToEnqueue as $jobToEnqueue) {
            $this->resqueQueueService->enqueue($jobToEnqueue);
        }

        $this->assertEquals($expectedContains, $this->resqueQueueService->contains($job));
    }

    public function containsSuccessDataProvider(): array
    {
        return [
            'empty queue' => [
                'jobsToEnqueue' => [],
                'job' => new RetrieveResourceJob(),
                'expectedContains' => false,
            ],
            'non-matching args (no keys)' => [
                'jobsToEnqueue' => [
                    new RetrieveResourceJob([
                        'id' => 'example-id',
                    ]),
                ],
                'job' => new RetrieveResourceJob([
                    'foo' => 'bar',
                ]),
                'expectedContains' => false,
            ],
            'non-matching args (no matching values)' => [
                'jobsToEnqueue' => [
                    new RetrieveResourceJob([
                        'id' => 'example-id',
                    ]),
                ],
                'job' => new RetrieveResourceJob([
                    'id' => 'non-matching-id',
                ]),
                'expectedContains' => false,
            ],
            'matching args' => [
                'jobsToEnqueue' => [
                    new RetrieveResourceJob([
                        'id' => 'example-id',
                    ]),
                ],
                'job' => new RetrieveResourceJob([
                    'id' => 'example-id',
                ]),
                'expectedContains' => true,
            ],
        ];
    }

    public function testContainsFailure()
    {
        $credisException = \Mockery::mock(\CredisException::class);

        $job = new RetrieveResourceJob();

        /* @var Mock|Resque $resque */
        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('getQueue')
            ->with($job::QUEUE_NAME)
            ->andThrow($credisException);

        /* @var LoggerInterface|Mock $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('warning')
            ->with('ResqueQueueService::contains: Redis error []');

        $resqueQueueService = $this->createQueueService($resque, $logger);

        $this->assertFalse($resqueQueueService->contains($job));
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
