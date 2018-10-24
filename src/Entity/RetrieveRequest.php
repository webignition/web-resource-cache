<?php

namespace App\Entity;

use App\Model\RequestIdentifier;
use Doctrine\ORM\Mapping as ORM;
use webignition\HttpHeaders\Headers;

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
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private $hash = '';

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

    public function setHash(RequestIdentifier $requestIdentifier)
    {
        $this->hash = (string) $requestIdentifier;
    }

    public function getHash(): string
    {
        return $this->hash;
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

    public function setHeaders(Headers $headers)
    {
        $this->headers = $headers->toArray();
    }

    public function getHeaders(): Headers
    {
        return new Headers($this->headers);
    }
}
