<?php

namespace App\Tests\Unit\Model;

use App\Model\RetrieveRequest;
use webignition\HttpHeaders\Headers;

class RetrieveRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $requestHash
     * @param string $url
     * @param Headers $headers
     * @param $retryCount
     */
    public function testCreate(string $requestHash, string $url, Headers $headers, $retryCount)
    {
        $retrieveRequest = new RetrieveRequest($requestHash, $url, $headers, $retryCount);

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
