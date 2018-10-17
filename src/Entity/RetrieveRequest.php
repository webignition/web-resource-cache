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

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private $hash = '';

    public function __construct()
    {
        $this->generateHash();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
        $this->generateHash();
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function addCallbackUrl(string $callbackUrl)
    {
        if (!in_array($callbackUrl, $this->callbackUrls)) {
            $this->callbackUrls[] = $callbackUrl;
            sort($this->callbackUrls);
            $this->generateHash();
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

    public function setHeader(string $key, $value)
    {
        if (!is_scalar($value) && !is_null($value)) {
            return false;
        }

        $key = strtolower($key);

        $this->headers[$key] = $value;
        asort($this->headers);

        $this->generateHash();

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

    public function getHash(): string
    {
        return $this->hash;
    }

    private function generateHash()
    {
        $this->hash = md5($this->url . json_encode($this->headers) .  json_encode($this->callbackUrls));
    }

}
