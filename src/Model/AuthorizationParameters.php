<?php

namespace App\Model;

class AuthorizationParameters
{
    private $host;

    public function __construct(string $host)
    {
        $this->host = $host;
    }

    public function getHost(): string
    {
        return $this->host;
    }
}
