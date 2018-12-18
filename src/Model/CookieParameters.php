<?php

namespace App\Model;

class CookieParameters
{
    private $domain;
    private $path;

    public function __construct(string $domain, string $path)
    {
        $this->domain = $domain;
        $this->path = $path;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
