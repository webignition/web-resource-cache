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
        $data = json_decode(trim($json), true);

        if (!is_array($data)) {
            return null;
        }

        $requestId = $data['request_id'] ?? null;

        if (empty($requestId)) {
            return null;
        }

        return new static($requestId);
    }
}
