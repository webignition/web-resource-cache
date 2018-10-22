<?php

namespace App\Model;

use App\Entity\Resource;

class SuccessResponse extends AbstractResponse
{
    /**
     * @var Resource
     */
    private $resource;

    public function __construct(RequestIdentifier $requestIdentifier, Resource $resource)
    {
        parent::__construct($requestIdentifier, self::STATUS_SUCCESS);

        $this->resource = $resource;
    }
}
