<?php

namespace App\Tests\Unit\Resque;

use App\Resque\Job\RetrieveResourceJob;

class RetrieveResourceJobTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $id = 'example-id';
        $retrieveResourceJob = new RetrieveResourceJob([
            'id' => $id,
        ]);

        $this->assertEquals(RetrieveResourceJob::QUEUE_NAME, $retrieveResourceJob->queue);
        $this->assertEquals(['id' => $id], $retrieveResourceJob->args);
    }

    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $id = 'example-id';
        $retrieveResourceJob = new RetrieveResourceJob([
            'id' => $id,
        ]);

        $this->assertTrue($retrieveResourceJob->run([]));
    }
}
