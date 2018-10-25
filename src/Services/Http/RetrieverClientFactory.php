<?php

namespace App\Services\Http;

use GuzzleHttp\Cookie\CookieJarInterface;

class RetrieverClientFactory extends ClientFactory
{
    /**
     * @var CookieJarInterface
     */
    private $cookieJar;

    public function __construct(CookieJarInterface $cookieJar)
    {
        $this->cookieJar = $cookieJar;
    }

    protected function createClientConfig(): array
    {
        return array_merge(parent::createClientConfig(), [
            'cookies' => $this->cookieJar,
        ]);
    }
}
