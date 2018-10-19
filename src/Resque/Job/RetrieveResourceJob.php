<?php

namespace App\Resque\Job;

use App\Command\RetrieveResourceCommand;
use Symfony\Component\Console\Command\Command;

class RetrieveResourceJob extends AbstractCommandJob
{
    const QUEUE_NAME = 'retrieve-resource';

    protected function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    protected function getCommand(): Command
    {
        /* @var RetrieveResourceCommand $command */
        $command = $this->getContainer()->get(RetrieveResourceCommand::class);

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
