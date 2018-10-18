<?php

namespace App\Tests\Unit\Model;

use App\Model\RequestIdentifier;

class RequestIdentifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $url
     * @param array $headers
     * @param string $expectedHash
     */
    public function testCreate(string $url, array $headers, string $expectedHash)
    {
        $requestIdentifier = new RequestIdentifier($url, $headers);

        $this->assertEquals($expectedHash, $requestIdentifier->getHash());
    }

    public function createDataProvider(): array
    {
        return [
            'no headers' => [
                'url' => 'http://example.com/',
                'headers' => [],
                'expectedHash' => '4c2297fd8f408fa415ebfbc2d991f9ce',
            ],
            'has headers, all invalid' => [
                'url' => 'http://example.com/',
                'headers' => [
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ],
                'expectedHash' => '4c2297fd8f408fa415ebfbc2d991f9ce',
            ],
            'has headers, all valid' => [
                'url' => 'http://example.com/',
                'headers' => [
                    'foo' => 'bar',
                ],
                'expectedHash' => 'eb4e3044ea80f54bfcb69f8d209b416e',
            ],
            'has headers, some valid' => [
                'url' => 'http://example.com/',
                'headers' => [
                    'foo' => 'bar',
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ],
                'expectedHash' => 'eb4e3044ea80f54bfcb69f8d209b416e',
            ],
        ];
    }

    public function testHeaderSetOrderDoesNotAffectHash()
    {
        $url = 'http://example.com/';

        $identifier1 = new RequestIdentifier($url, [
            'foo' => 'bar',
            'fizz' => 'buzz',
        ]);

        $identifier2 = new RequestIdentifier($url, [
            'fizz' => 'buzz',
            'foo' => 'bar',
        ]);

        $this->assertEquals($identifier1->getHash(), $identifier2->getHash());
    }

//    /**
//     * @dataProvider setHeaderValidValueTypeDataProvider
//     *
//     * @param string|int $value
//     */
//    public function testSetHeaderValidValueType($value)
//    {
//        $retrieveRequest = new RequestIdentifier();
//
//        $this->assertTrue($retrieveRequest->setHeader('foo', $value));
//        $this->assertEquals(
//            [
//                'foo' => $value,
//            ],
//            $retrieveRequest->getHeaders()
//        );
//    }
//
//    public function setHeaderValidValueTypeDataProvider(): array
//    {
//        return [
//            'string' => [
//                'value' => 'bar',
//            ],
//            'integer' => [
//                'value' => 12,
//            ],
//        ];
//    }

//    /**
//     * @dataProvider setHeaderInvalidValueTypeDataProvider
//     *
//     * @param mixed $value
//     */
//    public function testSetHeaderInvalidValueType($value)
//    {
//        $retrieveRequest = new RetrieveRequest();
//
//        $this->assertFalse($retrieveRequest->setHeader('foo', $value));
//    }
//
//    public function setHeaderInvalidValueTypeDataProvider(): array
//    {
//        return [
//            'boolean' => [
//                'value' => true,
//            ],
//            'array' => [
//                'value' => [1, 2, 3],
//            ],
//            'object' => [
//                'value' => (object)[1, 2, 3],
//            ],
//        ];
//    }
}
