<?php

namespace App\Services;

use App\Model\WhiteListItemInterface;
use App\Model\WhitelistItem;

class WhitelistItemFactory
{
    public function create(array $itemData): ?WhiteListItemInterface
    {
        $value = $itemData['value'] ?? null;
        $value = trim($value);

        if (empty($value)) {
            return null;
        }

        return new WhitelistItem($value);
    }
}
