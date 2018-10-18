<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Resource
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $url = '';

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $headers = [];

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $body = '';

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setHeader(string $key, $value)
    {
        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        $key = strtolower($key);

        $this->headers[$key] = $value;
        asort($this->headers);

        return true;
    }

    public function setHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setBody(string $body)
    {
        $this->body = $body;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
