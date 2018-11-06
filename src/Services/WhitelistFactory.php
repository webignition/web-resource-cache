<?php

namespace App\Services;

class WhitelistFactory
{
    public function create(string $allowedHostsString)
    {
        $allowedHostsString = trim($allowedHostsString);

        $allowedHosts = empty($allowedHostsString) ? [] : explode(',', $allowedHostsString);

        return new Whitelist($allowedHosts);
    }
}
