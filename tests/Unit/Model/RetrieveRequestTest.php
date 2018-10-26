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
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                    RetrieveRequest::JSON_KEY_URL => 'http://example.com/',
                    RetrieveRequest::JSON_KEY_HEADERS => [],
                    RetrieveRequest::JSON_KEY_RETRY_COUNT => 0,
                ],
            ],
            'integer retry count' => [
                'requestHash' => 'request_hash',
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'retryCount' => 1,
                'expectedArray' => [
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                    RetrieveRequest::JSON_KEY_URL => 'http://example.com/',
                    RetrieveRequest::JSON_KEY_HEADERS => [],
                    RetrieveRequest::JSON_KEY_RETRY_COUNT => 1,
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
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                    RetrieveRequest::JSON_KEY_URL => 'http://example.com/',
                    RetrieveRequest::JSON_KEY_HEADERS => [
                        'foo'=> [
                            'bar',
                        ],
                        'fizz' => [
                            'buzz',
                        ],
                    ],
                    RetrieveRequest::JSON_KEY_RETRY_COUNT => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider createFromJsonInvalidDataDataProvider
     *
     * @param string $json
     */
    public function testCreateFromJsonInvalidData(string $json)
    {
        $retrieveRequest = RetrieveRequest::createFromJson($json);

        $this->assertNull($retrieveRequest);
    }

    public function createFromJsonInvalidDataDataProvider(): array
    {
        return [
            'empty string' => [
                'json' => '',
            ],
            'not an array (string)' => [
                'json' => json_encode('string'),
            ],
            'not an array (bool)' => [
                'json' => json_encode(true),
            ],
            'missing request_hash' => [
                'json' => json_encode([]),
            ],
            'missing url' => [
                'json' => json_encode([
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                ]),
            ],
            'missing headers' => [
                'json' => json_encode([
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                    RetrieveRequest::JSON_KEY_URL => 'http://example.com/',
                ]),
            ],
            'missing retry_count' => [
                'json' => json_encode([
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                    RetrieveRequest::JSON_KEY_URL => 'http://example.com/',
                    RetrieveRequest::JSON_KEY_HEADERS => [],
                ]),
            ],
        ];
    }

    /**
     * @dataProvider createFromJsonValidDataDataProvider
     *
     * @param string $json
     * @param array $expectedArray
     */
    public function testCreateFromJsonValidData(string $json, array $expectedArray)
    {
        $retrieveRequest = RetrieveRequest::createFromJson($json);

        $this->assertEquals($expectedArray, $retrieveRequest->jsonSerialize());
    }

    public function createFromJsonValidDataDataProvider(): array
    {
        return [
            'empty headers' => [
                'json' => json_encode([
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                    RetrieveRequest::JSON_KEY_URL => 'http://example.com/',
                    RetrieveRequest::JSON_KEY_HEADERS => [],
                    RetrieveRequest::JSON_KEY_RETRY_COUNT => 0,
                ]),
                'expectedArray' => [
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                    RetrieveRequest::JSON_KEY_URL => 'http://example.com/',
                    RetrieveRequest::JSON_KEY_HEADERS => [],
                    RetrieveRequest::JSON_KEY_RETRY_COUNT => 0,
                ],
            ],
            'has headers' => [
                'json' => json_encode([
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                    RetrieveRequest::JSON_KEY_URL => 'http://example.com/',
                    RetrieveRequest::JSON_KEY_HEADERS => [
                        'foo'=> [
                            'bar',
                        ],
                        'fizz' => [
                            'buzz',
                        ],
                    ],
                    RetrieveRequest::JSON_KEY_RETRY_COUNT => 1,
                ]),
                'expectedArray' => [
                    RetrieveRequest::JSON_KEY_REQUEST_HASH => 'request_hash',
                    RetrieveRequest::JSON_KEY_URL => 'http://example.com/',
                    RetrieveRequest::JSON_KEY_HEADERS => [
                        'foo'=> [
                            'bar',
                        ],
                        'fizz' => [
                            'buzz',
                        ],
                    ],
                    RetrieveRequest::JSON_KEY_RETRY_COUNT => 1,
                ],
            ],
        ];
    }
}
