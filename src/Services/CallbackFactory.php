<?php

namespace App\Services;

use App\Entity\Callback as CallbackEntity;

class CallbackFactory
{
    public function create(string $requestHash, string $url): CallbackEntity
    {
        $callback = new CallbackEntity();
        $callback->setRequestHash($requestHash);
        $callback->setUrl($url);

        return $callback;
    }
}
