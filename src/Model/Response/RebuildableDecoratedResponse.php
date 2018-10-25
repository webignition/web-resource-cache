<?php

namespace App\Model\Response;

class RebuildableDecoratedResponse implements ResponseInterface
{
    /**
     * @var AbstractResponse
     */
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function jsonSerialize(): array
    {
        return array_merge($this->response->jsonSerialize(), [
            'class' => get_class($this->response),
        ]);
    }

    public function getRequestId(): string
    {
        return $this->response->getRequestId();
    }
}
