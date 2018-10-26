<?php

namespace App\Model;

use webignition\HttpHeaders\Headers;

class RetrieveRequest
{
    /**
     * @var string
     */
    private $requestHash;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var int
     */
    private $retryCount = 0;

    public function __construct(string $requestHash, string $url, Headers $headers, ?int $retryCount = 0)
    {
        $this->requestHash = $requestHash;
        $this->url = $url;
        $this->headers = $headers->toArray();
        $this->retryCount = $retryCount ?? 0;
    }

    public function getRequestHash(): string
    {
        return $this->requestHash;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHeaders(): Headers
    {
        return new Headers($this->headers);
    }

    public function incrementRetryCount()
    {
        $this->retryCount++;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }
}
