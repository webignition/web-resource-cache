<?php

namespace App\Tests\Unit\Message;

use App\Message\SendResponse;

class SendResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $responseData = [
            'request_id' => 'request_hash',
            'status' => 'success',
        ];

        $message = new SendResponse($responseData);

        $this->assertEquals($responseData, $message->getResponseData());
    }
}
