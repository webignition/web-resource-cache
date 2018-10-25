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
        $data = static::decodeJson($json);

        if (empty($data)) {
            return null;
        }

        return new static($data['request_id']);
    }
}
