<?php

namespace App\Tests\Unit\Resque;

use App\Resque\Job\RetrieveResourceJob;

class RetrieveResourceJobTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $requestHash = 'example-hash';
        $retrieveResourceJob = new RetrieveResourceJob([
            'request-hash' => $requestHash,
        ]);

        $this->assertEquals(RetrieveResourceJob::QUEUE_NAME, $retrieveResourceJob->queue);
        $this->assertEquals(['request-hash' => $requestHash], $retrieveResourceJob->args);
    }

    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $requestHash = 'example-hash';
        $retrieveResourceJob = new RetrieveResourceJob([
            'request-hash' => $requestHash,
        ]);

        $this->assertTrue($retrieveResourceJob->run([]));
    }
}
