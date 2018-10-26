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
            return !is_null($this->findJobInQueue($job));
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

    private function findJobInQueue(Job $job): ?Job
    {
        $jobs = $this->resque->getQueue($job->queue)->getJobs();

        foreach ($jobs as $foundJob) {
            /* @var $foundJob Job */

            if ($this->match($foundJob, $job)) {
                return $foundJob;
            }
        }

        return null;
    }

    private function match(Job $foundJob, Job $comparatorJob): bool
    {
        $args = $comparatorJob->args;

        foreach ($args as $key => $value) {
            if (!isset($foundJob->args[$key])) {
                return false;
            }

            if ($foundJob->args[$key] != $value) {
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
