<?php

namespace App\Model;

use webignition\HttpHeaders\Headers;

class RetrieveRequest implements \JsonSerializable
{
    const JSON_KEY_REQUEST_HASH = 'request_hash';
    const JSON_KEY_URL = 'url';
    const JSON_KEY_HEADERS = 'headers';
    const JSON_KEY_RETRY_COUNT = 'retry_count';

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

    public function __construct(string $requestHash, string $url, ?Headers $headers = null, ?int $retryCount = 0)
    {
        $headers = $headers ?? new Headers();

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
            self::JSON_KEY_REQUEST_HASH => $this->requestHash,
            self::JSON_KEY_URL => $this->url,
            self::JSON_KEY_HEADERS => $this->headers,
            self::JSON_KEY_RETRY_COUNT => $this->retryCount,
        ];
    }

    public static function createFromJson(string $json): ?RetrieveRequest
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            return null;
        }

        $requestHash = $data[self::JSON_KEY_REQUEST_HASH] ?? null;
        $url = $data[self::JSON_KEY_URL] ?? null;
        $headers = $data[self::JSON_KEY_HEADERS] ?? null;
        $retryCount = $data[self::JSON_KEY_RETRY_COUNT] ?? null;

        if (empty($requestHash) || empty($url) || !is_array($headers) || null === $retryCount) {
            return null;
        }

        return new RetrieveRequest($requestHash, $url, new Headers($headers), $retryCount);
    }
}
