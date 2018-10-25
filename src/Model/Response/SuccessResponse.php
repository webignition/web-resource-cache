<?php

namespace App\Model\Response;

class SuccessResponse extends AbstractResponse
{
    public function __construct(string $requestHash)
    {
        parent::__construct($requestHash, self::STATUS_SUCCESS);
    }

    public static function fromJson(string $json): ?ResponseInterface
    {
        $data = json_decode($json, true);

        return new static($data['request_id']);
    }
}
