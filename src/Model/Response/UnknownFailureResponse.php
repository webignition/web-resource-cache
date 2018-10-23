<?php

namespace App\Model\Response;

use App\Model\RequestIdentifier;

class UnknownFailureResponse extends AbstractFailureResponse
{
    public function __construct(RequestIdentifier $requestIdentifier)
    {
        parent::__construct($requestIdentifier, self::TYPE_UNKNOWN);
    }
}
