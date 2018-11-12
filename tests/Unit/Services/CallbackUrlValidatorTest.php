<?php

namespace App\Tests\Unit\Services;

use App\Services\CallbackUrlValidator;

class CallbackUrlValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider matchesDataProvider
     *
     * @param array $allowedHosts
     * @param string $url
     * @param bool $expectedIsValid
     */
    public function testIsValid(array $allowedHosts, string $url, bool $expectedIsValid)
    {
        $callbackUrlValidator = new CallbackUrlValidator($allowedHosts);

        $this->assertSame($expectedIsValid, $callbackUrlValidator->isValid($url));
    }

    public function matchesDataProvider(): array
    {
        return [
            'empty host list does not match' => [
                'allowedHosts' => [],
                'url' => 'http://example.com/',
                'expectedIsValid' => false,
            ],
            'no matching items does not match' => [
                'allowedHosts' => [
                    'foo',
                ],
                'url' => 'http://example.com/',
                'expectedIsValid' => false,
            ],
            'direct allowed host, sub-domain of allowed host does not match' => [
                'allowedHosts' => [
                    'example.com',
                ],
                'url' => 'http://foo.example.com/',
                'expectedIsValid' => false,
            ],
            'direct allowed host, domain of allowed host does match' => [
                'allowedHosts' => [
                    'example.com',
                ],
                'url' => 'http://example.com/',
                'expectedIsValid' => true,
            ],
            'wildcard allowed host, domain of allowed host does match' => [
                'allowedHosts' => [
                    '*.example.com',
                ],
                'url' => 'http://example.com/',
                'expectedIsValid' => true,
            ],
            'wildcard allowed host, sub-domain of allowed host does match' => [
                'allowedHosts' => [
                    '*.example.com',
                ],
                'url' => 'http://foo.example.com/',
                'expectedIsValid' => true,
            ],
            'wildcard-only host matches everything (1)' => [
                'allowedHosts' => [
                    '*',
                ],
                'url' => 'http://example.com/',
                'expectedIsValid' => true,
            ],
        ];
    }
}
