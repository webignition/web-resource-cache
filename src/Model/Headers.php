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
        foreach ($headers as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function createHash(): string
    {
        return md5(json_encode($this->headers));
    }

    /**
     * @param string $key
     * @param string|int|null $value
     *
     * @return bool
     */
    public function set(string $key, $value)
    {
        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        $key = strtolower($key);

        $this->headers[$key] = $value;
        asort($this->headers);

        return true;
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
}
