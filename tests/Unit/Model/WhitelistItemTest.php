<?php

namespace App\Tests\Unit\Model;

use App\Model\WhitelistItem;

class WhitelistItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider matchesDataProvider
     *
     * @param string $pattern
     * @param string $url
     * @param bool $expectedMatches
     */
    public function testMatches(string $pattern, string $url, bool $expectedMatches)
    {
        $item = new WhitelistItem($pattern);

        $this->assertSame($item->matches($url), $expectedMatches);
    }

    public function matchesDataProvider(): array
    {
        return [
            'non-matching pattern' => [
                'pattern' => '/^foo/',
                'url' => 'http://example.com',
                'expectedMatches' => false,
            ],
            'matching pattern' => [
                'pattern' => '/^http:\/\/foo/',
                'url' => 'http://foo.example.com',
                'expectedMatches' => true,
            ],
        ];
    }
}
