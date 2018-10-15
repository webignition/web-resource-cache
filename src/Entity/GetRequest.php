<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class GetRequest
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $callbackUrl;

    public function getId(): ?int
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

    public function setCallbackUrl(string $callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->callbackUrl;
    }
}
