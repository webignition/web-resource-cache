<?php

namespace App\Resque\Job;

use App\Command\SendResponseCommand;
use Symfony\Component\Console\Command\Command;

class ReturnResourceJob extends AbstractResourceJob
{
    const QUEUE_NAME = 'return-resource';

    protected function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    protected function getCommand(): Command
    {
        /* @var SendResponseCommand $command */
        $command = $this->getContainer()->get(SendResponseCommand::class);

        return $command;
    }
}
