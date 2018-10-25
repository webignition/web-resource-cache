<?php

namespace App\Model\Response;

class RebuildableDecoratedResponse implements \JsonSerializable
{
    /**
     * @var AbstractResponse
     */
    private $response;

    public function __construct(AbstractResponse $response)
    {
        $this->response = $response;
    }

    public function jsonSerialize(): array
    {
        return array_merge($this->response->jsonSerialize(), [
            'class' => get_class($this->response),
        ]);
    }
}
