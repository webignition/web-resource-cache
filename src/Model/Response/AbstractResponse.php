<?php

namespace App\Model\Response;

abstract class AbstractResponse implements ResponseInterface
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * @var string
     */
    private $requestHash;

    /**
     * @var string
     */
    private $status;

    public function __construct(string $requestHash, string $status)
    {
        $this->requestHash = $requestHash;
        $this->status = $status;
    }

    public function jsonSerialize(): array
    {
        return [
            'request_id' => $this->requestHash,
            'status' => $this->status,
        ];
    }

    protected static function decodeJson(string $json): ?array
    {
        $data = json_decode(trim($json), true);

        if (!is_array($data)) {
            return null;
        }

        $requestId = $data['request_id'] ?? null;

        if (empty($requestId)) {
            return null;
        }

        return $data;
    }
}
