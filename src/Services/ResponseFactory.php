<?php

namespace App\Services;

use App\Model\Response\AbstractFailureResponse;
use App\Model\Response\AbstractResponse;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\ResponseInterface;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;

class ResponseFactory
{
    private $allowedStatus = [
        AbstractResponse::STATUS_SUCCESS,
        AbstractResponse::STATUS_FAILED,
    ];

    private $allowedFailureType = [
        AbstractFailureResponse::TYPE_HTTP,
        AbstractFailureResponse::TYPE_CONNECTION,
        AbstractFailureResponse::TYPE_UNKNOWN,
    ];

    public function createFromArray(array $data): ?ResponseInterface
    {
        $requestId = $data['request_id'] ?? null;

        if (empty($requestId)) {
            return null;
        }

        $status = $data['status'] ?? null;

        if (!in_array($status, $this->allowedStatus)) {
            return null;
        }

        if (AbstractResponse::STATUS_SUCCESS === $status) {
            return new SuccessResponse($requestId);
        }

        $failureType = $data['failure_type'] ?? null;

        if (!in_array($failureType, $this->allowedFailureType)) {
            return null;
        }

        if (AbstractFailureResponse::TYPE_UNKNOWN === $failureType) {
            return new UnknownFailureResponse($requestId);
        }

        $statusCode = $data['status_code'] ?? null;

        if (null === $statusCode) {
            return null;
        }

        return new KnownFailureResponse($requestId, $failureType, $statusCode);
    }
}
