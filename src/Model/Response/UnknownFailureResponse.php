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
        $data = static::decodeJson($json);

        if (empty($data)) {
            return null;
        }

        return new static($data['request_id']);
    }
}
