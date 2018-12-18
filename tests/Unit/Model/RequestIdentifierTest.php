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
     * @param array $parameters
     * @param string $expectedHash
     */
    public function testCreate(string $url, array $parameters, string $expectedHash)
    {
        $requestIdentifier = new RequestIdentifier($url, $parameters);

        $this->assertEquals($expectedHash, $requestIdentifier->getHash());
    }

    public function createDataProvider(): array
    {
        return [
            'no headers' => [
                'url' => 'http://example.com/',
                'parameters' => [],
                'expectedHash' => '4c2297fd8f408fa415ebfbc2d991f9ce',
            ],
            'has headers, all invalid' => [
                'url' => 'http://example.com/',
                'parameters' => [
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ],
                'expectedHash' => '7fadf3ab7b21c766c93585c2097d17b0',
            ],
            'has headers, all valid' => [
                'url' => 'http://example.com/',
                'parameters' => [
                    'foo' => 'bar',
                ],
                'expectedHash' => 'eb4e3044ea80f54bfcb69f8d209b416e',
            ],
            'has headers, some valid' => [
                'url' => 'http://example.com/',
                'parameters' => [
                    'foo' => 'bar',
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ],
                'expectedHash' => 'f21761dbec3e762077f7f8370f169247',
            ],
        ];
    }
}
