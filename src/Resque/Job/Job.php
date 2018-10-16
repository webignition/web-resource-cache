<?php

namespace App\Resque\Job;

use ResqueBundle\Resque\ContainerAwareJob;

abstract class Job extends ContainerAwareJob
{
    abstract protected function getQueueName(): string;

    public function __construct($args = [])
    {
        parent::__construct($args);
        $this->queue = $this->getQueueName();
    }
}
