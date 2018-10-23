<?php

namespace App\Model\Response;

class ConnectionFailureResponse extends AbstractKnownFailureResponse
{
    public function __construct(string $requestHash, int $statusCode)
    {
        parent::__construct($requestHash, $statusCode, self::TYPE_CONNECTION);
    }
}
