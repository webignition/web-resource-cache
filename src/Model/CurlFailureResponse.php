<?php

namespace App\Model;

class CurlFailureResponse extends AbstractFailureResponse
{
    public function __construct(RequestIdentifier $requestIdentifier, int $statusCode)
    {
        parent::__construct($requestIdentifier, $statusCode, self::TYPE_CURL);
    }
}
