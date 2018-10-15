<?php

namespace App\Services;

class WhitelistFactory
{
    public function create(string $patternsString)
    {
        $patterns = explode(',', $patternsString);

        return new Whitelist($patterns);
    }
}
