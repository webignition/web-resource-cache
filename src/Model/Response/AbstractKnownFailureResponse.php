<?php

namespace App\Model\Response;

use App\Model\RequestIdentifier;

abstract class AbstractKnownFailureResponse extends AbstractFailureResponse
{
    /**
     * @var int
     */
    private $statusCode;

    public function __construct(RequestIdentifier $requestIdentifier, int $statusCode, string $type)
    {
        parent::__construct($requestIdentifier, $type);

        $this->statusCode = $statusCode;
    }

    public function toScalarArray(): array
    {
        return array_merge(parent::toScalarArray(), [
            'status_code' => $this->statusCode,
        ]);
    }
}
