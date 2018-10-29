<?php

namespace App\Tests\Unit\Message;

use App\Message\SendResponse;
use App\Model\Response\ResponseInterface;

class SendResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $requestHash = 'request_hash';
        $response = \Mockery::mock(ResponseInterface::class);

        $message = new SendResponse($requestHash, $response);

        $this->assertEquals($requestHash, $message->getRequestHash());
        $this->assertEquals($response, $message->getResponse());
    }
}
