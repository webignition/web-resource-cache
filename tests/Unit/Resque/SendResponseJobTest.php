<?php

namespace App\Tests\Unit\Resque;

use App\Entity\CachedResource;
use App\Model\Response\AbstractResponse;
use App\Model\Response\ConnectionFailureResponse;
use App\Model\Response\HttpFailureResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Resque\Job\SendResponseJob;

class SendResponseJobTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider responseDataProvider
     *
     * @param AbstractResponse $response
     */
    public function testCreateFoo(AbstractResponse $response)
    {
        $responseJson = json_encode($response);

        $sendResponseJob = new SendResponseJob([
            'response-json' => json_encode($response),
        ]);

        $this->assertEquals(SendResponseJob::QUEUE_NAME, $sendResponseJob->queue);
        $this->assertEquals(['response-json' => $responseJson], $sendResponseJob->args);
    }

    /**
     * @dataProvider responseDataProvider
     *
     * @param AbstractResponse $response
     *
     * @throws \Exception
     */
    public function testRun(AbstractResponse $response)
    {
        $sendResponseJob = new SendResponseJob([
            'response-json' => json_encode($response),
        ]);

        $this->assertTrue($sendResponseJob->run([]));
    }

    /**
     * @return array
     */
    public function responseDataProvider(): array
    {
        return [
            'unknown failure response' => [
                'response' => new UnknownFailureResponse('request_hash_1'),
            ],
            'connection failure response' => [
                'response' => new ConnectionFailureResponse('request_hash_2', 28),
            ],
            'http failure response' => [
                'response' => new HttpFailureResponse('request_hash_3', 404),
            ],
            'success response' => [
                'response' => new SuccessResponse('request_hash_4', new CachedResource()),
            ],
        ];
    }
}
