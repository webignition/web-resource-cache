<?php

namespace App\Services;

class CallbackUrlValidator
{
    /**
     * @var string[]
     */
    private $allowedHosts = [];

    public function __construct(array $allowedHosts = [])
    {
        $this->allowedHosts = $allowedHosts;
    }

    public function isValid(string $url)
    {
        $urlHost = parse_url($url, PHP_URL_HOST);

        if (in_array($urlHost, $this->allowedHosts)) {
            return true;
        }

        foreach ($this->allowedHosts as $allowedHost) {
            if (substr($allowedHost, 0, 2) === '*.') {
                $comparatorAllowedHost = substr($allowedHost, 2);

                if ($comparatorAllowedHost === $urlHost) {
                    return true;
                }

                $allowedHostPattern = '/^.+\.' . preg_quote($comparatorAllowedHost) . '/';
                if (preg_match($allowedHostPattern, $urlHost)) {
                    return true;
                }
            }
        }

        return false;
    }
}
