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

    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param RetrieveResource $retrieveResourceMessage
     * @param array $expectedArray
     */
    public function testJsonSerialize(RetrieveResource $retrieveResourceMessage, array $expectedArray)
    {
        $this->assertEquals($expectedArray, $retrieveResourceMessage->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider(): array
    {
        return [
            'no headers, default retry count' => [
                'retrieveResourceMessage' => new RetrieveResource(
                    'request-hash-1',
                    'http://example.com/1/'
                ),
                'expectedArray' => [
                    'requestHash' => 'request-hash-1',
                    'url' => 'http://example.com/1/',
                    'headers' => [],
                    'retryCount' => 0,
                ],
            ],
            'no headers, non-default retry count' => [
                'retrieveResourceMessage' => new RetrieveResource(
                    'request-hash-1',
                    'http://example.com/1/',
                    new Headers(),
                    2
                ),
                'expectedArray' => [
                    'requestHash' => 'request-hash-1',
                    'url' => 'http://example.com/1/',
                    'headers' => [],
                    'retryCount' => 2,
                ],
            ],
            'has headers, default retry count' => [
                'retrieveResourceMessage' => new RetrieveResource(
                    'request-hash-1',
                    'http://example.com/1/',
                    new Headers([
                        'foo' => 'bar',
                        'fizz' => 'buzz',
                    ]),
                    0
                ),
                'expectedArray' => [
                    'requestHash' => 'request-hash-1',
                    'url' => 'http://example.com/1/',
                    'headers' => [
                        'foo' => [
                            'bar',
                        ],
                        'fizz' => [
                            'buzz',
                        ],
                    ],
                    'retryCount' => 0,
                ],
            ],
        ];
    }
}
