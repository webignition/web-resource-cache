<?php

namespace App\Model\Response;

class KnownFailureResponse extends AbstractFailureResponse
{
    /**
     * @var int
     */
    private $statusCode;

    public function __construct(string $requestHash, string $type, int $statusCode)
    {
        parent::__construct($requestHash, $type);

        $this->statusCode = $statusCode;
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'status_code' => $this->statusCode,
        ]);
    }

    public static function fromJson(string $json): ?ResponseInterface
    {
        $data = static::decodeJson($json);

        if (empty($data)) {
            return null;
        }

        return new static($data['request_id'], $data['failure_type'], $data['status_code']);
    }

    protected static function decodeJson(string $json): ?array
    {
        $data = parent::decodeJson($json);

        if (empty($data)) {
            return null;
        }

        $requestId = $data['request_id'] ?? null;
        $type = $data['failure_type'] ?? null;
        $statusCode = $data['status_code'] ?? null;

        if (empty($requestId) || empty($type) || null === $statusCode) {
            return null;
        }

        return $data;
    }
}
