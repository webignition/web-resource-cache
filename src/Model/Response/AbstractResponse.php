<?php

namespace App\Model\Response;

abstract class AbstractResponse implements \JsonSerializable
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
}
