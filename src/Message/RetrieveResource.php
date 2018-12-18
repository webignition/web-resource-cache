<?php

namespace App\Message;

use webignition\HttpHeaders\Headers;

class RetrieveResource implements \JsonSerializable
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
     * @var array
     */
    private $parameters = [];

    /**
     * @var int
     */
    private $retryCount = 0;

    public function __construct(
        string $requestHash,
        string $url,
        Headers $headers,
        array $parameters,
        ?int $retryCount = 0
    ) {
        $headers = $headers ?? new Headers();

        $this->requestHash = $requestHash;
        $this->url = $url;
        $this->headers = $headers->toArray();
        $this->parameters = $parameters;
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

    public function getParameters(): array
    {
        return $this->parameters;
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
            'requestHash' => $this->requestHash,
            'url' => $this->url,
            'headers' => $this->headers,
            'parameters' => $this->parameters,
            'retryCount' => $this->retryCount,
        ];
    }
}
