<?php

namespace App\Model\Response;

use App\Model\RequestIdentifier;

class ConnectionFailureResponse extends AbstractFailureResponse
{
    public function __construct(RequestIdentifier $requestIdentifier, int $statusCode)
    {
        parent::__construct($requestIdentifier, $statusCode, self::TYPE_CONNECTION);
    }
}
