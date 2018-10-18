<?php

namespace App\Model;

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

    public function __construct(string $url, array $headers = [])
    {
        $this->url = $url;
        $this->setHeaders($headers);
        $this->hash = md5($this->url . json_encode($this->headers));
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    private function setHeader(string $key, $value)
    {
        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        $key = strtolower($key);

        $this->headers[$key] = $value;
        asort($this->headers);

        return true;
    }

    private function setHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    public function __toString()
    {
        return $this->hash;
    }
}
