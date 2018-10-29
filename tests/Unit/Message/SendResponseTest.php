<?php

namespace App\Tests\Unit\Message;

use App\Message\SendResponse;
use App\Model\Response\ResponseInterface;

class SendResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $response = \Mockery::mock(ResponseInterface::class);

        $message = new SendResponse($response);

        $this->assertEquals($response, $message->getResponse());
    }
}
