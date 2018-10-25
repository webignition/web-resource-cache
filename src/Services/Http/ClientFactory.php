<?php

namespace App\Services\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class ClientFactory
{
    /**
     * @var array
     */
    protected $curlOptions;

    /**
     * @var HandlerStack
     */
    protected $handlerStack;

    public function __construct(array $curlOptions, HandlerStack $handlerStack)
    {
        $this->setCurlOptions($curlOptions);

        $this->handlerStack = $handlerStack;
    }

    public function create(): Client
    {
        return new Client($this->createClientConfig());
    }

    protected function createClientConfig(): array
    {
        return [
            'curl' => $this->curlOptions,
            'verify' => false,
            'handler' => $this->handlerStack,
            'max_retries' => HttpRetryMiddlewareFactory::MAX_RETRIES,
        ];
    }

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
