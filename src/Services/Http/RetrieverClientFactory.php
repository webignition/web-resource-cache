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

    public function __construct(array $curlOptions, HandlerStack $handlerStack, CookieJarInterface $cookieJar)
    {
        parent::__construct($curlOptions, $handlerStack);

        $this->cookieJar = $cookieJar;
    }

    protected function createClientConfig(): array
    {
        return array_merge(parent::createClientConfig(), [
            'cookies' => $this->cookieJar,
        ]);
    }
}
