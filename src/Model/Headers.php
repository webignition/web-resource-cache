<?php

namespace App\Model;

class Headers
{
    /**
     * @var array
     */
    private $headers = [];

    public function __construct(array $headers = [])
    {
        $this->headers = $this->filter($headers);
    }

    public function createHash(): string
    {
        return md5(json_encode($this->headers));
    }

    /**
     * @param string $key
     * @param string|int|null $value
     *
     * @return Headers
     */
    public function withHeader(string $key, $value): Headers
    {
        return new Headers(array_merge($this->headers, $this->filter([$key => $value])));
    }

    /**
     * @param string $key
     *
     * @return string|int|null
     */
    public function get(string $key)
    {
        return $this->headers[$key] ?? null;
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    private function filter(array $headers): array
    {
        $filteredHeaders = [];

        foreach ($headers as $key => $value) {
            if (!is_string($value) && !is_int($value)) {
                continue;
            }

            $key = strtolower($key);

            $filteredHeaders[$key] = $value;
            asort($filteredHeaders);
        }

        return $filteredHeaders;
    }
}
