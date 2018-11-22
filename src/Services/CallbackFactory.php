<?php

namespace App\Services;

use App\Entity\Callback as CallbackEntity;

class CallbackFactory
{
    public function create(string $requestHash, string $url, bool $logResponse): CallbackEntity
    {
        $callback = new CallbackEntity();
        $callback->setRequestHash($requestHash);
        $callback->setUrl($url);
        $callback->setLogResponse($logResponse);

        return $callback;
    }
}
