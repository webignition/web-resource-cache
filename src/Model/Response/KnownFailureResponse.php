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
        $data = json_decode(trim($json), true);

        if (!is_array($data)) {
            return null;
        }

        $requestId = $data['request_id'] ?? null;
        $type = $data['failure_type'] ?? null;
        $statusCode = $data['status_code'] ?? null;

        if (empty($requestId) || empty($type) || null === $statusCode) {
            return null;
        }

        return new static($requestId, $type, $statusCode);
    }
}
