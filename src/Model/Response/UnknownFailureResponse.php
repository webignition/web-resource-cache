<?php

namespace App\Model\Response;

class UnknownFailureResponse extends AbstractFailureResponse
{
    public function __construct(string $requestHash)
    {
        parent::__construct($requestHash, self::TYPE_UNKNOWN);
    }
}
