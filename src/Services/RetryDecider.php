<?php

namespace App\Services;

class RetryDecider
{
    const TYPE_HTTP = 'http';
    const TYPE_CURL = 'curl';

    /**
     * @var array
     */
    private $retryableStatusData;

    public function __construct(array $retryableStatusData)
    {
        $this->retryableStatusData = $retryableStatusData;
    }

    public function isRetryable(string $type, int $code): bool
    {
        if (!array_key_exists($type, $this->retryableStatusData)) {
            return false;
        }

        return in_array($code, $this->retryableStatusData[$type]);
    }
}
