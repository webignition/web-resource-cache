<?php

namespace App\Services\Http;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpHandlerStackFactory
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
     * @var callable|null
     */
    private $handler;

    /**
     * @param HttpHistoryContainer $historyContainer
     * @param CacheMiddleware|null $cacheMiddleware
     * @param callable|null $handler
     */
    public function __construct(
        HttpHistoryContainer $historyContainer,
        CacheMiddleware $cacheMiddleware = null,
        callable $handler = null
    ) {
        $this->historyContainer = $historyContainer;
        $this->cacheMiddleware = $cacheMiddleware;
        $this->handler = $handler;
    }

    /**
     * @return HandlerStack
     */
    public function create()
    {
        $handlerStack = HandlerStack::create($this->handler);

        if ($this->cacheMiddleware) {
            $handlerStack->push($this->cacheMiddleware, self::MIDDLEWARE_CACHE_KEY);
        }

        $handlerStack->push(Middleware::history($this->historyContainer), self::MIDDLEWARE_HISTORY_KEY);

        return $handlerStack;
    }
}
