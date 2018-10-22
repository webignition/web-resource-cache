<?php

namespace App\Tests\Unit\Resque;

use App\Resque\Job\SendResponseJob;

class SendResponseJobTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $id = 'example-id';
        $returnResourceJob = new SendResponseJob([
            'id' => $id,
        ]);

        $this->assertEquals(SendResponseJob::QUEUE_NAME, $returnResourceJob->queue);
        $this->assertEquals(['id' => $id], $returnResourceJob->args);
    }

    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $id = 'example-id';
        $retrieveResourceJob = new SendResponseJob([
            'id' => $id,
        ]);

        $this->assertTrue($retrieveResourceJob->run([]));
    }
}
