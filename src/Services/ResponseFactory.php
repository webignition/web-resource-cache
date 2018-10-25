<?php

namespace App\Services;

use App\Model\Response\ResponseInterface;

class ResponseFactory
{
    public function createFromJson(string $json)
    {
        $data = json_decode(trim($json), true);

        if (!is_array($data)) {
            return null;
        }

        $class = $data['class'] ?? null;

        if (empty($class)) {
            return null;
        }

        if (!class_exists($class)) {
            return null;
        }

        if (!in_array(ResponseInterface::class, class_implements($class))) {
            return null;
        }

        return $class::fromJson($json);
    }
}
