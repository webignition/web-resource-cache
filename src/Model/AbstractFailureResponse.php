<?php

namespace App\Model;

abstract class AbstractFailureResponse extends AbstractResponse
{
    const TYPE_HTTP = 'http';
    const TYPE_CURL = 'curl';

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
}
