<?php

namespace App\Tests\Unit\Resque;

use App\Resque\Job\ReturnResourceJob;

class ReturnResourceJobTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $id = 'example-id';
        $returnResourceJob = new ReturnResourceJob([
            'id' => $id,
        ]);

        $this->assertEquals(ReturnResourceJob::QUEUE_NAME, $returnResourceJob->queue);
        $this->assertEquals(['id' => $id], $returnResourceJob->args);
    }

    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $id = 'example-id';
        $retrieveResourceJob = new ReturnResourceJob([
            'id' => $id,
        ]);

        $this->assertTrue($retrieveResourceJob->run([]));
    }
}
