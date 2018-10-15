<?php

namespace App\Model;

class WhitelistItem implements WhiteListItemInterface
{
    /**
     * @var string
     */
    private $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function matches(string $url): bool
    {
        return preg_match($this->pattern, $url) > 0;
    }
}
