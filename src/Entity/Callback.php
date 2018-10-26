<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\Table(
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="hash_url_unique", columns={"request_hash", "url_hash"})
 *    }
 * )
 */
class Callback
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
     * @ORM\Column(type="string", length=32)
     */
    private $requestHash;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    private $urlHash;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $retryCount = 0;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setRequestHash(string $requestHash)
    {
        $this->requestHash = (string) $requestHash;
    }

    public function getRequestHash(): ?string
    {
        return $this->requestHash;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
        $this->urlHash = md5($url);
    }

    public function getUrl(): ?string
    {
        return $this->url;
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
