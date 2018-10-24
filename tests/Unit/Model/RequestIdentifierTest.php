<?php

namespace App\Tests\Unit\Model;

use App\Model\RequestIdentifier;
use webignition\HttpHeaders\Headers;

class RequestIdentifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $url
     * @param Headers $headers
     * @param string $expectedHash
     */
    public function testCreate(string $url, Headers $headers, string $expectedHash)
    {
        $requestIdentifier = new RequestIdentifier($url, $headers);

        $this->assertEquals($expectedHash, $requestIdentifier->getHash());
    }

    public function createDataProvider(): array
    {
        return [
            'no headers' => [
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'expectedHash' => '118e35f631be802c41bec5c9dfb0f415',
            ],
            'has headers, all invalid' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ]),
                'expectedHash' => '00bd01b2277e9401e89dc3431afe0db6',
            ],
            'has headers, all valid' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                ]),
                'expectedHash' => 'e0db2182d9cc7589763876fb7cee314f',
            ],
            'has headers, some valid' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ]),
                'expectedHash' => 'c1ec8873271ff238158cfa672cbfb253',
            ],
        ];
    }
}
