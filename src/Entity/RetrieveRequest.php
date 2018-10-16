<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="url_idx", columns={"url"}, options={"length": 255})
 *     }
 * )
 */
class RetrieveRequest
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
    private $url;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $retryCount = 0;

    /**
     * @var array
     *
     * @ORM\Column(type="simple_array")
     */
    private $callbackUrls = [];

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

    public function addCallbackUrl(string $callbackUrl)
    {
        if (!in_array($callbackUrl, $this->callbackUrls)) {
            $this->callbackUrls[] = $callbackUrl;
        }
    }

    /**
     * @return string[]
     */
    public function getCallbackUrls(): array
    {
        return $this->callbackUrls;
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
