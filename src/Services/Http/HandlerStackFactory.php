<?php

namespace App\Services\Http;

use App\Services\ArrayCollection;
use GuzzleHttp\HandlerStack;

class HandlerStackFactory
{
    public function create(ArrayCollection $middlewareCollection = null, callable $handler = null): HandlerStack
    {
        $handlerStack = HandlerStack::create($handler);

        if ($middlewareCollection) {
            foreach ($middlewareCollection->get() as $name => $middleware) {
                $handlerStack->push($middleware, $name);
            }
        }

        return $handlerStack;
    }
}
