<?php

namespace App\Model;

abstract class AbstractResponse
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
}
