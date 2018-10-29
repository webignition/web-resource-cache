<?php

namespace App\Tests\Unit\Message;

use App\Message\RetrieveResource;
use webignition\HttpHeaders\Headers;

class RetrieveResourceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $requestHash
     * @param string $url
     * @param Headers $headers
     * @param int|null $retryCount
     */
    public function testCreate(string $requestHash, string $url, Headers $headers, $retryCount)
    {
        $retrieveRequest = new RetrieveResource($requestHash, $url, $headers, $retryCount);

        $this->assertEquals($requestHash, $retrieveRequest->getRequestHash());
        $this->assertEquals($url, $retrieveRequest->getUrl());
        $this->assertEquals($headers, $retrieveRequest->getHeaders());
        $this->assertEquals($retryCount, $retrieveRequest->getRetryCount());
    }

    public function createDataProvider(): array
    {
        return [
            'null retry count' => [
                'requestHash' => 'request_hash',
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'retryCount' => null,
            ],
            'integer retry count' => [
                'requestHash' => 'request_hash',
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'retryCount' => 2,
            ],
        ];
    }
}
