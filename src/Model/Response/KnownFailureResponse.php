<?php

namespace App\Model\Response;

class KnownFailureResponse extends AbstractFailureResponse
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var array
     */
    private $context = [];

    public function __construct(string $requestHash, string $type, int $statusCode, array $context = [])
    {
        parent::__construct($requestHash, $type);

        $this->statusCode = $statusCode;
        $this->context = $context;
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'status_code' => $this->statusCode,
            'context' => $this->context,
        ]);
    }
}
