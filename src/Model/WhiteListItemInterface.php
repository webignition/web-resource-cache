<?php

namespace App\Model;

interface WhiteListItemInterface
{
    public function matches(string $url): bool;
}
