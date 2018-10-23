<?php

namespace App\Resque\Job;

use App\Command\SendResponseCommand;
use Symfony\Component\Console\Command\Command;

class SendResponseJob extends AbstractCommandJob
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

    protected function getCommandArgs(): array
    {
        return [
            'response-json' => $this->args['response-json']
        ];
    }

    protected function getIdentifier(): string
    {
        return $this->args['response-json'];
    }
}
