<?php

namespace App\Resque\Job;

use App\Command\RetrieveResourceCommand;
use Symfony\Component\Console\Command\Command;

class GetResourceJob extends CommandJob
{
    const QUEUE_NAME = 'resource-get';

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
