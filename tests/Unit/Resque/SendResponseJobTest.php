<?php

namespace App\Tests\Unit\Resque;

use App\Resque\Job\SendResponseJob;

class SendResponseJobTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $requestHash = 'example-hash';
        $returnResourceJob = new SendResponseJob([
            'request-hash' => $requestHash,
        ]);

        $this->assertEquals(SendResponseJob::QUEUE_NAME, $returnResourceJob->queue);
        $this->assertEquals(['request-hash' => $requestHash], $returnResourceJob->args);
    }

    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $requestHash = 'example-hash';
        $retrieveResourceJob = new SendResponseJob([
            'request-hash' => $requestHash,
        ]);

        $this->assertTrue($retrieveResourceJob->run([]));
    }
}
