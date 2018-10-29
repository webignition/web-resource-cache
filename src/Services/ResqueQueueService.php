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
    protected $resque;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Resque $resque, LoggerInterface $logger)
    {
        $this->resque = $resque;
        $this->logger = $logger;
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
