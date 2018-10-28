<?php

namespace App\Services;

class WhitelistFactory
{
    public function create(string $patternsString)
    {
        $patternsString = trim($patternsString);

        $patterns = empty($patternsString) ? [] : explode(',', $patternsString);

        foreach ($patterns as $patterIndex => $pattern) {
            $patterns[$patterIndex] = '/' . trim($pattern) . '/';
        }

        return new Whitelist($patterns);
    }
}
