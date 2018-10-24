<?php

namespace App\Entity;

use App\Model\RequestIdentifier;
use Doctrine\ORM\Mapping as ORM;
use webignition\HttpHeaders\Headers;

/**
 * @ORM\Entity
 */
class CachedResource
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private $requestHash = '';

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

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $lastStored;

    public function __construct()
    {
        $this->lastStored = new \DateTime();
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setHeaders(Headers $headers)
    {
        $this->headers = $headers->toArray();
    }

    public function getHeaders(): Headers
    {
        return new Headers($this->headers);
    }

    public function setBody(string $body)
    {
        $this->body = $body;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setRequestHash(string $requestHash)
    {
        $this->requestHash = (string) $requestHash;
    }

    public function getRequestHash(): string
    {
        return $this->requestHash;
    }

    public function setLastStored(\DateTime $lastStored)
    {
        $this->lastStored = $lastStored;
    }

    public function getLastStored(): \DateTime
    {
        return $this->lastStored;
    }

    public function getStoredAge(\DateTime $now = null): int
    {
        if (empty($now)) {
            $now = new \DateTime();
        }

        return $now->getTimestamp() - $this->lastStored->getTimestamp();
    }
}
