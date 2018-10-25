<?php

namespace App\Services\Http;

use App\Services\ArrayCollection;
use GuzzleHttp\Middleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class SenderMiddlewareCollection extends ArrayCollection
{
    const MIDDLEWARE_HISTORY_KEY = 'history';

    public function __construct(HttpHistoryContainer $historyContainer)
    {
        parent::__construct([
            self::MIDDLEWARE_HISTORY_KEY => Middleware::history($historyContainer),
        ]);
    }
}
