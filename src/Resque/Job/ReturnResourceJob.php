<?php

namespace App\Resque\Job;

use App\Command\ReturnResourceCommand;
use Symfony\Component\Console\Command\Command;

class ReturnResourceJob extends AbstractCommandJob
{
    const QUEUE_NAME = 'return-resource';

    protected function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    protected function getCommand(): Command
    {
        /* @var ReturnResourceCommand $command */
        $command = $this->getContainer()->get(ReturnResourceCommand::class);

        return $command;
    }

    protected function getCommandArgs(): array
    {
        return [
            'id' => $this->args['id']
        ];
    }

    protected function getIdentifier(): string
    {
        return $this->args['id'];
    }
}
