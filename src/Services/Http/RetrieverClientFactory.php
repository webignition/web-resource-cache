<?php

namespace App\Services\Http;

use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\HandlerStack;

class RetrieverClientFactory extends ClientFactory
{
    /**
     * @var CookieJarInterface
     */
    private $cookieJar;

    public function __construct(HandlerStack $handlerStack, CookieJarInterface $cookieJar)
    {
        parent::__construct($handlerStack);

        $this->cookieJar = $cookieJar;
    }

    protected function createClientConfig(): array
    {
        return array_merge(parent::createClientConfig(), [
            'cookies' => $this->cookieJar,
        ]);
    }
}
