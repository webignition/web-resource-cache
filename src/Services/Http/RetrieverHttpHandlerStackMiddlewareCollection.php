<?php

namespace App\Services\Http;

use App\Services\ArrayCollection;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class RetrieverHttpHandlerStackMiddlewareCollection extends ArrayCollection
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_RETRY_KEY = 'retry';
    const MIDDLEWARE_HISTORY_KEY = 'history';

    public function __construct(
        HttpHistoryContainer $historyContainer,
        HttpRetryMiddlewareFactory $httpRetryMiddlewareFactory,
        CacheMiddleware $cacheMiddleware
    ) {
        parent::__construct([
            self::MIDDLEWARE_CACHE_KEY => $cacheMiddleware,
            self::MIDDLEWARE_RETRY_KEY => $httpRetryMiddlewareFactory->create(),
            self::MIDDLEWARE_HISTORY_KEY => Middleware::history($historyContainer),
        ]);
    }
}
