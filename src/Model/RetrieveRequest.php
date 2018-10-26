<?php

namespace App\Model;

use webignition\HttpHeaders\Headers;

class RetrieveRequest implements \JsonSerializable
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

    public function jsonSerialize(): array
    {
        return [
            'request_hash' => $this->requestHash,
            'url' => $this->url,
            'headers' => $this->headers,
            'retry_count' => $this->retryCount,
        ];
    }
}
