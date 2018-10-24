<?php

namespace App\Model\Response;

abstract class AbstractFailureResponse extends AbstractResponse
{
    const TYPE_HTTP = 'http';
    const TYPE_CONNECTION = 'connection';
    const TYPE_UNKNOWN = 'unknown';

    /**
     * @var string
     */
    private $type;

    public function __construct(string $requestHash, string $type)
    {
        parent::__construct($requestHash, self::STATUS_FAILED);

        $this->type = $type;
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'failure_type' => $this->type,
        ]);
    }
}
