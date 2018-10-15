<?php

namespace App\Tests\Unit\Services;

use App\Model\WhitelistItem;
use App\Services\WhitelistItemFactory;

class WhitelistItemFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createInvalidDataDataProvider
     *
     * @param array $itemData
     */
    public function testCreateInvalidItemData(array $itemData)
    {
        $factory = new WhitelistItemFactory();

        $this->assertNull($factory->create($itemData));
    }

    public function createInvalidDataDataProvider(): array
    {
        return [
            'invalid value (null)' => [
                'itemData' => [
                    'value' => null,
                ],
            ],
            'invalid value (empty)' => [
                'itemData' => [
                    'value' => '',
                ],
            ],
        ];
    }

    /**
     * @dataProvider createSuccessDataProvider
     *
     * @param array $itemData
     */
    public function testCreateSuccess(array $itemData)
    {
        $factory = new WhitelistItemFactory();

        $this->assertInstanceOf(WhitelistItem::class, $factory->create($itemData));
    }

    public function createSuccessDataProvider(): array
    {
        return [
            'valid' => [
                'itemData' => [
                    'value' => '/foo/',
                ],
            ],
        ];
    }
}
