<?php

namespace App\Model;

class HttpFailureResponse extends AbstractFailureResponse
{
    public function __construct(RequestIdentifier $requestIdentifier, int $statusCode)
    {
        parent::__construct($requestIdentifier, $statusCode, self::TYPE_HTTP);
    }
}
