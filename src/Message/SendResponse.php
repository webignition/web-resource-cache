<?php

namespace App\Message;

class SendResponse
{
    /**
     * @var string
     */
    private $requestHash;


    public function __construct(string $requestHash)
    {
        $this->requestHash = $requestHash;
    }

    public function getRequestHash(): string
    {
        return $this->requestHash;
    }
}
