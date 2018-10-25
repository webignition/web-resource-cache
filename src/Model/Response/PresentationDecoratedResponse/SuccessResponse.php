<?php

namespace App\Model\Response\PresentationDecoratedResponse;

use App\Entity\CachedResource;
use App\Model\Response\SuccessResponse as BaseSuccessResponse;

class SuccessResponse implements \JsonSerializable
{
    /**
     * @var BaseSuccessResponse
     */
    private $response;

    /**
     * @var CachedResource
     */
    private $resource;

    public function __construct(BaseSuccessResponse $response, CachedResource $resource)
    {
        $this->response = $response;
        $this->resource = $resource;
    }

    public function jsonSerialize(): array
    {
        return array_merge($this->response->jsonSerialize(), [
            'headers' => $this->resource->getHeaders()->toArray(),
            'content' => $this->resource->getBody(),
        ]);
    }
}
