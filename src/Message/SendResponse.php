<?php

namespace App\Message;

use App\Model\Response\ResponseInterface;

class SendResponse
{
    /**
     * @var string
     */
    private $requestHash;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(string $requestHash, ResponseInterface $response)
    {
        $this->requestHash = $requestHash;
        $this->response = $response;
    }

    public function getRequestHash(): string
    {
        return $this->requestHash;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
