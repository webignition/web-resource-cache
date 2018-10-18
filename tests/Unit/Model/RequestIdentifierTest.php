<?php

namespace App\Tests\Unit\Model;

use App\Model\Headers;
use App\Model\RequestIdentifier;

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
                'expectedHash' => '118e35f631be802c41bec5c9dfb0f415',
            ],
            'has headers, all valid' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                ]),
                'expectedHash' => 'fda3da5c3d7c68725e824361e55a1b93',
            ],
            'has headers, some valid' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ]),
                'expectedHash' => 'fda3da5c3d7c68725e824361e55a1b93',
            ],
        ];
    }
}
