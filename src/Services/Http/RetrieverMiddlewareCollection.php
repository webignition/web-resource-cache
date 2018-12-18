<?php

namespace App\Services\Http;

use App\Services\ArrayCollection;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class RetrieverMiddlewareCollection extends ArrayCollection
{
    const MIDDLEWARE_HTTP_AUTHENTICATION_KEY = 'http-authentication';
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_RETRY_KEY = 'retry';
    const MIDDLEWARE_HISTORY_KEY = 'history';

    public function __construct(
        HttpAuthenticationMiddleware $httpAuthenticationMiddleware,
        HttpHistoryContainer $historyContainer,
        RetryMiddlewareFactory $retryMiddlewareFactory,
        CacheMiddleware $cacheMiddleware
    ) {
        parent::__construct([
            self::MIDDLEWARE_HTTP_AUTHENTICATION_KEY => $httpAuthenticationMiddleware,
            self::MIDDLEWARE_CACHE_KEY => $cacheMiddleware,
            self::MIDDLEWARE_RETRY_KEY => $retryMiddlewareFactory->create(),
            self::MIDDLEWARE_HISTORY_KEY => Middleware::history($historyContainer),
        ]);
    }
}
