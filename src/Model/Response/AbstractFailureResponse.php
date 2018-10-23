<?php

namespace App\Model\Response;

use App\Model\RequestIdentifier;

abstract class AbstractFailureResponse extends AbstractResponse
{
    const TYPE_HTTP = 'http';
    const TYPE_CONNECTION = 'connection';

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $type;

    public function __construct(RequestIdentifier $requestIdentifier, int $statusCode, string $type)
    {
        parent::__construct($requestIdentifier, self::STATUS_FAILED);

        $this->statusCode = $statusCode;
        $this->type = $type;
    }

    public function toScalarArray(): array
    {
        return array_merge(parent::toScalarArray(), [
            'failure_type' => $this->type,
            'status_code' => $this->statusCode,
        ]);
    }

    public function jsonSerialize(): array
    {
        return $this->toScalarArray();
    }
}
