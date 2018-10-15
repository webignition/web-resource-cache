<?php

namespace App\Services;

use App\Model\WhiteListItemInterface;

class Whitelist
{
    /**
     * @var WhitelistItemFactory
     */
    private $itemFactory;

    /**
     * @var WhiteListItemInterface[]
     */
    private $whitelistItems = [];

    public function __construct(WhitelistItemFactory $itemFactory, array $whitelistItemData = [])
    {
        $this->itemFactory = $itemFactory;

        foreach ($whitelistItemData as $itemData) {
            $item = $itemFactory->create($itemData);

            if ($item) {
                $this->whitelistItems[] = $item;
            }
        }
    }

    public function matches(string $url)
    {
        foreach ($this->whitelistItems as $whiteListItem) {
            if ($whiteListItem->matches($url)) {
                return true;
            }
        }

        return false;
    }
}
