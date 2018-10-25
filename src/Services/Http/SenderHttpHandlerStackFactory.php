<?php

namespace App\Services\Http;

use GuzzleHttp\HandlerStack;

class SenderHttpHandlerStackFactory
{
    /**
     * @var callable|null
     */
    private $handler;

    public function __construct(callable $handler = null)
    {
        $this->handler = $handler;
    }

    /**
     * @return HandlerStack
     */
    public function create()
    {
        return HandlerStack::create($this->handler);
    }
}
