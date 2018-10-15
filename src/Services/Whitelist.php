<?php

namespace App\Services;

use App\Model\WhitelistItem;
use App\Model\WhiteListItemInterface;

class Whitelist
{
    /**
     * @var WhiteListItemInterface[]
     */
    private $whitelistItems = [];

    public function __construct(array $whitelistPatterns = [])
    {
        foreach ($whitelistPatterns as $pattern) {
            $this->whitelistItems[] = new WhitelistItem(trim($pattern));
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
