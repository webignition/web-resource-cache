<?php

namespace App\Services\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\HandlerStack;

class HttpClientFactory
{
    /**
     * @var array
     */
    private $curlOptions;

    /**
     * @var CookieJarInterface
     */
    private $cookieJar;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    public function __construct(array $curlOptions, HandlerStack $handlerStack, CookieJarInterface $cookieJar)
    {
        $this->setCurlOptions($curlOptions);

        $this->cookieJar = $cookieJar;
        $this->handlerStack = $handlerStack;
    }

    /**
     * @return Client
     */
    public function create()
    {
        return new Client([
            'curl' => $this->curlOptions,
            'verify' => false,
            'handler' => $this->handlerStack,
            'max_retries' => HttpRetryMiddlewareFactory::MAX_RETRIES,
            'cookies' => $this->cookieJar,
        ]);
    }

    /**
     * @param array $curlOptions
     */
    private function setCurlOptions(array $curlOptions)
    {
        $definedCurlOptions = [];

        foreach ($curlOptions as $name => $value) {
            if (defined($name)) {
                $definedCurlOptions[constant($name)] = $value;
            }
        }

        $this->curlOptions = $definedCurlOptions;
    }
}
