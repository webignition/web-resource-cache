<?php

namespace App\Tests\Unit\Services;

use App\Services\Whitelist;
use App\Services\WhitelistItemFactory;

class WhitelistTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider matchesDataProvider
     *
     * @param array $whitelistData
     * @param string $url
     * @param bool $expectedMatches
     */
    public function testMatches(array $whitelistData, string $url, bool $expectedMatches)
    {
        $itemFactory = new WhitelistItemFactory();
        $whitelist = new Whitelist($itemFactory, $whitelistData);

        $this->assertSame($expectedMatches, $whitelist->matches($url));
    }

    public function matchesDataProvider(): array
    {
        return [
            'empty whitelist does not match' => [
                'whitelistData' => [],
                'url' => 'http://example.com/',
                'expectedMatches' => false,
            ],
            'no matching items does not match' => [
                'whitelistData' => [
                    [
                        'value' => '/foo/',
                    ],
                ],
                'url' => 'http://example.com/',
                'expectedMatches' => false,
            ],
            'matching items does match (1)' => [
                'whitelistData' => [
                    [
                        'value' => '/^http:\/\/[a-z]+\.example\.com\/$/',
                    ],
                ],
                'url' => 'http://foo.example.com/',
                'expectedMatches' => true,
            ],
            'matching items does match (2)' => [
                'whitelistData' => [
                    [
                        'value' => '/^http:\/\/[a-z]+\.example\.com\/$/',
                    ],
                ],
                'url' => 'http://bar.example.com/',
                'expectedMatches' => true,
            ],
            'matching items does match (3)' => [
                'whitelistData' => [
                    [
                        'value' => '/^http:\/\/[a-z]+\.example\.com\/$/',
                    ],
                    [
                        'value' => '/foo/',
                    ],
                ],
                'url' => 'http://bar.foo.com/',
                'expectedMatches' => true,
            ],
        ];
    }
}
