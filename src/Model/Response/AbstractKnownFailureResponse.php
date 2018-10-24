<?php

namespace App\Model\Response;

abstract class AbstractKnownFailureResponse extends AbstractFailureResponse
{
    /**
     * @var int
     */
    private $statusCode;

    public function __construct(string $requestHash, int $statusCode, string $type)
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
}
