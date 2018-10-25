<?php

namespace App\Model\Response;

class UnknownFailureResponse extends AbstractFailureResponse
{
    public function __construct(string $requestHash)
    {
        parent::__construct($requestHash, self::TYPE_UNKNOWN);
    }

    public static function fromJson(string $json): ?ResponseInterface
    {
        $data = json_decode($json, true);

        return new static($data['request_id']);
    }
}
