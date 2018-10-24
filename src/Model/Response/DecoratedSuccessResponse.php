<?php

namespace App\Model\Response;

use App\Entity\CachedResource;

class DecoratedSuccessResponse extends SuccessResponse
{
    /**
     * @var CachedResource
     */
    private $resource;

    public function __construct(string $requestHash, CachedResource $resource)
    {
        parent::__construct($requestHash);

        $this->resource = $resource;
    }

    public function jsonSerialize(): array
    {
        return array_merge($this->toScalarArray(), [
            'headers' => $this->resource->getHeaders()->toArray(),
            'content' => $this->resource->getBody(),
        ]);
    }
}
