<?php

namespace App\Tests\Unit\Message;

use App\Message\SendResponse;

class SendResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $requestHash = 'request_hash';

        $retrieveRequest = new SendResponse($requestHash);

        $this->assertEquals($requestHash, $retrieveRequest->getRequestHash());
    }
}
