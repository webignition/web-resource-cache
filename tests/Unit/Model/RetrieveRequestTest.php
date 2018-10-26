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
     * @param int|null $retryCount
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

    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param string $requestHash
     * @param string $url
     * @param Headers $headers
     * @param int $retryCount
     * @param array $expectedArray
     */
    public function testJsonSerialize(
        string $requestHash,
        string $url,
        Headers $headers,
        ?int $retryCount,
        array $expectedArray
    ) {
        $retrieveRequest = new RetrieveRequest($requestHash, $url, $headers, $retryCount);

        $this->assertEquals($requestHash, $retrieveRequest->getRequestHash());
        $this->assertEquals($url, $retrieveRequest->getUrl());
        $this->assertEquals($headers, $retrieveRequest->getHeaders());
        $this->assertEquals($retryCount, $retrieveRequest->getRetryCount());

        $this->assertEquals($expectedArray, $retrieveRequest->jsonSerialize());
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'null retry count' => [
                'requestHash' => 'request_hash',
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'retryCount' => null,
                'expectedArray' => [
                    'request_hash' => 'request_hash',
                    'url' => 'http://example.com/',
                    'headers' => [],
                    'retry_count' => 0,
                ],
            ],
            'integer retry count' => [
                'requestHash' => 'request_hash',
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'retryCount' => 1,
                'expectedArray' => [
                    'request_hash' => 'request_hash',
                    'url' => 'http://example.com/',
                    'headers' => [],
                    'retry_count' => 1,
                ],
            ],
            'has headers retry count' => [
                'requestHash' => 'request_hash',
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                    'fizz' => 'buzz',
                ]),
                'retryCount' => 1,
                'expectedArray' => [
                    'request_hash' => 'request_hash',
                    'url' => 'http://example.com/',
                    'headers' => [
                        'foo'=> [
                            'bar',
                        ],
                        'fizz' => [
                            'buzz',
                        ],
                    ],
                    'retry_count' => 1,
                ],
            ],
        ];
    }
}
