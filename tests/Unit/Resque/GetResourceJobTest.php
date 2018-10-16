<?php

namespace App\Tests\Unit\Resque;

use App\Resque\Job\GetResourceJob;

class GetResourceJobTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $id = 'example-id';
        $getResourceJob = new GetResourceJob($id);

        $this->assertEquals('resource-get', $getResourceJob->queue);
        $this->assertEquals(['id' => $id], $getResourceJob->args);
    }
}
