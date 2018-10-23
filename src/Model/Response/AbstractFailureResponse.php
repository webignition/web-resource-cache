<?php

namespace App\Model\Response;

use App\Model\RequestIdentifier;

abstract class AbstractFailureResponse extends AbstractResponse
{
    const TYPE_HTTP = 'http';
    const TYPE_CONNECTION = 'connection';
    const TYPE_UNKNOWN = 'unknown';

    /**
     * @var string
     */
    private $type;

    public function __construct(RequestIdentifier $requestIdentifier, string $type)
    {
        parent::__construct($requestIdentifier, self::STATUS_FAILED);

        $this->type = $type;
    }

    public function toScalarArray(): array
    {
        return array_merge(parent::toScalarArray(), [
            'failure_type' => $this->type,
        ]);
    }

    public function jsonSerialize(): array
    {
        return $this->toScalarArray();
    }
}
