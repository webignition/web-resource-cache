<?php

namespace App\Resque\Job;

abstract class AbstractResourceJob extends AbstractCommandJob
{
    protected function getCommandArgs(): array
    {
        return [
            'request-hash' => $this->args['request-hash']
        ];
    }

    protected function getIdentifier(): string
    {
        return $this->args['request-hash'];
    }
}
