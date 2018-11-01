<?php

namespace App\Services;

use App\Model\Response\KnownFailureResponse;
use App\Model\Response\ResponseInterface;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;

class ResponseFactory
{
    public function createFromArray(array $data): ?ResponseInterface
    {
        $modelClass = $data['class'] ?? null;

        if (empty($modelClass)) {
            return null;
        }

        if (!$this->validateData($data, $modelClass)) {
            return null;
        }

        if (KnownFailureResponse::class === $modelClass) {
            return new KnownFailureResponse($data['request_id'], $data['failure_type'], $data['status_code']);
        }

        if (UnknownFailureResponse::class === $modelClass) {
            return new UnknownFailureResponse($data['request_id']);
        }

        return new SuccessResponse($data['request_id']);
    }

    public function createFromJson(string $json): ?ResponseInterface
    {
        $data = $this->decodeJson($json);

        if (empty($data)) {
            return null;
        }

        return $this->createFromArray($data);
    }

    private function decodeJson(string $json)
    {
        $data = json_decode(trim($json), true);

        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    private function validateData(array $data, string $modelClass)
    {
        if (!class_exists($modelClass)) {
            return null;
        }

        if (!in_array(ResponseInterface::class, class_implements($modelClass))) {
            return null;
        }

        $requestId = $data['request_id'] ?? null;

        if (empty($requestId)) {
            return null;
        }

        if (KnownFailureResponse::class === $modelClass) {
            $requestId = $data['request_id'] ?? null;
            $type = $data['failure_type'] ?? null;
            $statusCode = $data['status_code'] ?? null;

            if (empty($requestId) || empty($type) || null === $statusCode) {
                return null;
            }
        }

        return $data;
    }
}
