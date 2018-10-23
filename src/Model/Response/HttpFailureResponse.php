<?php

namespace App\Model\Response;

class HttpFailureResponse extends AbstractKnownFailureResponse
{
    public function __construct(string $requestHash, int $statusCode)
    {
        parent::__construct($requestHash, $statusCode, self::TYPE_HTTP);
    }
}
