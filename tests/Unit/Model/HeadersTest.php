<?php

namespace App\Tests\Unit\Model;

use App\Model\Headers;

class HeadersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param array $headersArray
     * @param array $expectedHeaders
     */
    public function testCreate(array $headersArray, array $expectedHeaders)
    {
        $headers = new Headers($headersArray);

        $this->assertEquals($expectedHeaders, $headers->toArray());
    }

    public function createDataProvider(): array
    {
        return [
            'empty' => [
                'headersArray' => [],
                'expectedHeaders' => [],
            ],
            'all invalid' => [
                'headersArray' => [
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ],
                'expectedHeaders' => [],
            ],
            'all valid' => [
                'headersArray' => [
                    'foo' => 'bar',
                ],
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
            'some valid' => [
                'headersArray' => [
                    'foo' => 'bar',
                    'boolean' => true,
                    'array' => [],
                    'object' => (object) [],
                ],
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider setDataProvider
     *
     * @param array $existingHeaders
     * @param string $key
     * @param $value
     * @param array $expectedHeaders
     */
    public function testSet(array $existingHeaders, string $key, $value, array $expectedHeaders)
    {
        $headers = new Headers($existingHeaders);
        $headers->set($key, $value);

        $this->assertEquals($expectedHeaders, $headers->toArray());
    }

    public function setDataProvider(): array
    {
        return [
            'no existing headers' => [
                'existingHeaders' => [],
                'key' => 'foo',
                'value' => 'bar',
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
            'no existing headers, add invalid' => [
                'existingHeaders' => [],
                'key' => 'foo',
                'value' => true,
                'expectedHeaders' => [],
            ],
            'has existing headers, add invalid' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'key' => 'foo',
                'value' => true,
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
            'has existing headers, add new valid' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'key' => 'fizz',
                'value' => 'buzz',
                'expectedHeaders' => [
                    'foo' => 'bar',
                    'fizz' => 'buzz',
                ],
            ],
            'has existing headers, overwrite' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'key' => 'foo',
                'value' => 'buzz',
                'expectedHeaders' => [
                    'foo' => 'buzz',
                ],
            ],
            'headers are sorted' => [
                'existingHeaders' => [
                    'zebra' => 'stripey monochrome horse',
                ],
                'key' => 'ant',
                'value' => 'tiny insect',
                'expectedHeaders' => [
                    'ant' => 'tiny insect',
                    'zebra' => 'stripey monochrome horse',
                ],
            ],
        ];
    }

    /**
     * @dataProvider createHashDataProvider
     *
     * @param array $existingHeaders
     * @param array $newHeaders
     * @param string $expectedHash
     */
    public function testCreateHash(array $existingHeaders, array $newHeaders, string $expectedHash)
    {
        $headers = new Headers($existingHeaders);

        foreach ($newHeaders as $key => $value) {
            $headers->set($key, $value);
        }

        $this->assertEquals($expectedHash, $headers->createHash());
    }

    public function createHashDataProvider(): array
    {
        return [
            'no existing headers, no new headers' => [
                'existingHeaders' => [],
                'newHeaders' => [],
                'expectedHash' => 'd751713988987e9331980363e24189ce',
            ],
            'has existing headers, no new headers' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'newHeaders' => [],
                'expectedHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
            ],
            'no existing headers, has new headers' => [
                'existingHeaders' => [],
                'newHeaders' => [
                    'foo' => 'bar',
                ],
                'expectedHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
            ],
            'has existing headers, has new headers' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'newHeaders' => [
                    'fizz' => 'buzz',
                ],
                'expectedHash' => '9ec0f73790c61c71905e8a3dc7dacbcc',
            ],
            'add order does not affect hash' => [
                'existingHeaders' => [
                    'fizz' => 'buzz',
                ],
                'newHeaders' => [
                    'foo' => 'bar',
                ],
                'expectedHash' => '9ec0f73790c61c71905e8a3dc7dacbcc',
            ],
        ];
    }

    public function testGet()
    {
        $headers = new Headers([
            'a' => 1,
            'foo' => 'bar',
        ]);

        $this->assertSame(1, $headers->get('a'));
        $this->assertSame('bar', $headers->get('foo'));
        $this->assertNull($headers->get('not-set'));
    }
}
