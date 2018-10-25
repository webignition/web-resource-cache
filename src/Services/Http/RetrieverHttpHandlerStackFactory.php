<?php

namespace App\Services\Http;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class RetrieverHttpHandlerStackFactory extends HttpHandlerStackFactory
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_HISTORY_KEY = 'history';

    /**
     * @var HttpHistoryContainer
     */
    private $historyContainer;

    /**
     * @var CacheMiddleware
     */
    private $cacheMiddleware;

    /**
     * @var Middleware
     */
    private $retryMiddleware;

    public function __construct(
        HttpHistoryContainer $historyContainer,
        HttpRetryMiddlewareFactory $httpRetryMiddlewareFactory,
        CacheMiddleware $cacheMiddleware = null,
        callable $handler = null
    ) {
        parent::__construct($handler);

        $this->historyContainer = $historyContainer;
        $this->retryMiddleware = $httpRetryMiddlewareFactory->create();
        $this->cacheMiddleware = $cacheMiddleware;
    }

    /**
     * @return HandlerStack
     */
    public function create()
    {
        $handlerStack = parent::create();

        if ($this->cacheMiddleware) {
            $handlerStack->push($this->cacheMiddleware, self::MIDDLEWARE_CACHE_KEY);
        }

        $handlerStack->push($this->retryMiddleware);
        $handlerStack->push(Middleware::history($this->historyContainer), self::MIDDLEWARE_HISTORY_KEY);

        return $handlerStack;
    }
}
