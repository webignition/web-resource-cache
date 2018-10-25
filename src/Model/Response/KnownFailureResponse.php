<?php

namespace App\Model\Response;

class KnownFailureResponse extends AbstractFailureResponse
{
    /**
     * @var int
     */
    private $statusCode;

    public function __construct(string $requestHash, string $type, int $statusCode)
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
