<?php

namespace App\Services;

class WhitelistFactory
{
    public function create(string $patternsString)
    {
        $patternsString = trim($patternsString);

        $patterns = empty($patternsString) ? [] : explode(',', $patternsString);

        return new Whitelist($patterns);
    }
}
