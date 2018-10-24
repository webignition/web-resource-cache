<?php

namespace App\Model;

use webignition\HttpHeaders\Headers;

class RequestIdentifier
{
    /**
     * @var string
     */
    private $url = '';

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $hash = '';

    public function __construct(string $url, Headers $headers)
    {
        $this->url = $url;
        $this->headers = $headers;
        $this->hash = md5($this->url . $this->headers->createHash());
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function __toString()
    {
        return $this->hash;
    }
}
