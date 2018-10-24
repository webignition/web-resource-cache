<?php

namespace App\Model\Response;

class SuccessResponse extends AbstractResponse
{
    public function __construct(string $requestHash)
    {
        parent::__construct($requestHash, self::STATUS_SUCCESS);
    }

    public function jsonSerialize(): array
    {
        return $this->toScalarArray();
    }
}
