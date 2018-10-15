<?php

namespace App\Tests\Unit\Services;

use App\Services\Whitelist;

class WhitelistTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider matchesDataProvider
     *
     * @param array $whitelistPatterns
     * @param string $url
     * @param bool $expectedMatches
     */
    public function testMatches(array $whitelistPatterns, string $url, bool $expectedMatches)
    {
        $whitelist = new Whitelist($whitelistPatterns);

        $this->assertSame($expectedMatches, $whitelist->matches($url));
    }

    public function matchesDataProvider(): array
    {
        return [
            'empty whitelist does not match' => [
                'whitelistPatterns' => [],
                'url' => 'http://example.com/',
                'expectedMatches' => false,
            ],
            'no matching items does not match' => [
                'whitelistPatterns' => [
                    '/foo/',
                ],
                'url' => 'http://example.com/',
                'expectedMatches' => false,
            ],
            'matching items does match (1)' => [
                'whitelistPatterns' => [
                    '/^http:\/\/[a-z]+\.example\.com\/$/',
                ],
                'url' => 'http://foo.example.com/',
                'expectedMatches' => true,
            ],
            'matching items does match (2)' => [
                'whitelistPatterns' => [
                    '/^http:\/\/[a-z]+\.example\.com\/$/',
                ],
                'url' => 'http://bar.example.com/',
                'expectedMatches' => true,
            ],
            'matching items does match (3)' => [
                'whitelistPatterns' => [
                    '/^http:\/\/[a-z]+\.example\.com\/$/',
                    '/foo/',
                ],
                'url' => 'http://bar.foo.com/',
                'expectedMatches' => true,
            ],
        ];
    }
}
