<?php

namespace App\Services;

class CallbackUrlValidatorFactory
{
    public function create(string $allowedHostsString)
    {
        $allowedHostsString = trim($allowedHostsString);

        $allowedHosts = empty($allowedHostsString) ? [] : explode(',', $allowedHostsString);

        return new CallbackUrlValidator($allowedHosts);
    }
}
