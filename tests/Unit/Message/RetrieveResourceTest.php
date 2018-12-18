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
     * @param array $parameters
     * @param int|null $retryCount
     */
    public function testCreate(string $requestHash, string $url, Headers $headers, array $parameters, ?int $retryCount)
    {
        $retrieveRequest = new RetrieveResource($requestHash, $url, $headers, $parameters, $retryCount);

        $this->assertEquals($requestHash, $retrieveRequest->getRequestHash());
        $this->assertEquals($url, $retrieveRequest->getUrl());
        $this->assertEquals($headers, $retrieveRequest->getHeaders());
        $this->assertEquals($parameters, $retrieveRequest->getParameters());
        $this->assertEquals($retryCount, $retrieveRequest->getRetryCount());
    }

    public function createDataProvider(): array
    {
        return [
            'null retry count' => [
                'requestHash' => 'request_hash',
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'parameters' => [],
                'retryCount' => null,
            ],
            'integer retry count' => [
                'requestHash' => 'request_hash',
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'parameters' => [],
                'retryCount' => 2,
            ],
            'non-empty parameters' => [
                'requestHash' => 'request_hash',
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'parameters' => [
                    'cookies' => [
                        'domain' => '.example.com',
                        'path' => '/',
                    ],
                ],
                'retryCount' => null,
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
            'no headers, no parameters, default retry count' => [
                'retrieveResourceMessage' => new RetrieveResource(
                    'request-hash-1',
                    'http://example.com/1/',
                    new Headers(),
                    []
                ),
                'expectedArray' => [
                    'requestHash' => 'request-hash-1',
                    'url' => 'http://example.com/1/',
                    'headers' => [],
                    'parameters' => [],
                    'retryCount' => 0,
                ],
            ],
            'no headers, no parameters, non-default retry count' => [
                'retrieveResourceMessage' => new RetrieveResource(
                    'request-hash-1',
                    'http://example.com/1/',
                    new Headers(),
                    [],
                    2
                ),
                'expectedArray' => [
                    'requestHash' => 'request-hash-1',
                    'url' => 'http://example.com/1/',
                    'headers' => [],
                    'parameters' => [],
                    'retryCount' => 2,
                ],
            ],
            'has headers, no parameters, default retry count' => [
                'retrieveResourceMessage' => new RetrieveResource(
                    'request-hash-1',
                    'http://example.com/1/',
                    new Headers([
                        'foo' => 'bar',
                        'fizz' => 'buzz',
                    ]),
                    [],
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
                    'parameters' => [],
                    'retryCount' => 0,
                ],
            ],
            'has headers, has parameters, default retry count' => [
                'retrieveResourceMessage' => new RetrieveResource(
                    'request-hash-1',
                    'http://example.com/1/',
                    new Headers([
                        'foo' => 'bar',
                        'fizz' => 'buzz',
                    ]),
                    [
                        'parameter-cookies' => [
                            'domain' => '.example.com',
                            'path' => '/',
                        ],
                    ],
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
                    'parameters' => [
                        'parameter-cookies' => [
                            'domain' => '.example.com',
                            'path' => '/',
                        ],
                    ],
                    'retryCount' => 0,
                ],
            ],
        ];
    }
}
