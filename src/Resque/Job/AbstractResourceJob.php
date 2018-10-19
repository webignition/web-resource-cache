<?php

namespace App\Resque\Job;

abstract class AbstractResourceJob extends AbstractCommandJob
{
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
