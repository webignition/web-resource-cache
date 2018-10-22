<?php

namespace App\Model\Response;

use App\Model\RequestIdentifier;

abstract class AbstractResponse implements ResponseInterface, \JsonSerializable
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * @var RequestIdentifier
     */
    private $requestIdentifier;

    /**
     * @var string
     */
    private $status;

    public function __construct(RequestIdentifier $requestIdentifier, string $status)
    {
        $this->requestIdentifier = $requestIdentifier;
        $this->status = $status;
    }

    public function toScalarArray(): array
    {
        return [
            'request_id' => (string) $this->requestIdentifier,
            'status' => $this->status,
        ];
    }
}
