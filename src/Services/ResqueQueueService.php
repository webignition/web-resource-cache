<?php

namespace App\Services;

use ResqueBundle\Resque\Resque;
use ResqueBundle\Resque\Job;
use Psr\Log\LoggerInterface;

/**
 * Wrapper for \ResqueBundle\Resque\Resque that handles exceptions
 * when trying to interact with queues.
 *
 * Exceptions generally occur when trying to establish a socket connection to
 * a redis server that does not exist. This can happen as in some environments
 * where the integration with redis is optional.
 *
 */
class ResqueQueueService
{
    const QUEUE_KEY = 'queue';

    /**
     * @var Resque
     */
    private $resque;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Resque $resque, LoggerInterface $logger)
    {
        $this->resque = $resque;
        $this->logger = $logger;
    }

    public function contains(Job $job): bool
    {
        try {
            return !is_null($this->findJobInQueue($job->queue, $job->args));
        } catch (\CredisException $credisException) {
            $this->logger->warning(
                'ResqueQueueService::contains: Redis error ['.$credisException->getMessage().']'
            );
        }

        return false;
    }

    public function enqueue(Job $job, bool $trackStatus = false): ?\Resque_Job_Status
    {
        try {
            return $this->resque->enqueue($job, $trackStatus);
        } catch (\CredisException $credisException) {
            $this->logger->warning('ResqueQueueService::enqueue: Redis error ['.$credisException->getMessage().']');
        }

        return null;
    }

    private function findJobInQueue(string $queue, ?array $args = []): ?Job
    {
        $jobs = $this->resque->getQueue($queue)->getJobs();

        foreach ($jobs as $job) {
            /* @var $job Job */

            if ($this->match($job, $args)) {
                return $job;
            }
        }

        return null;
    }

    private function match(Job $job, ?array $args = []): bool
    {
        foreach ($args as $key => $value) {
            if (!isset($job->args[$key])) {
                return false;
            }

            if ($job->args[$key] != $value) {
                return false;
            }
        }

        return true;
    }

    public function getQueueLength(string $queue): int
    {
        return \Resque::redis()->llen(self::QUEUE_KEY . ':' . $queue);
    }

    public function isEmpty(string $queue): bool
    {
        return $this->getQueueLength($queue) == 0;
    }
}
