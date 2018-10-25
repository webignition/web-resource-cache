<?php

namespace App\Services\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class ClientFactory
{
    /**
     * @var array
     */
    private $defaultConfig = [
        'verify' => false,
        'max_retries' => RetryMiddlewareFactory::MAX_RETRIES,
    ];

    /**
     * @var HandlerStack
     */
    protected $handlerStack;

    public function __construct(HandlerStack $handlerStack)
    {
        $this->handlerStack = $handlerStack;
    }

    public function create(array $curlOptions): Client
    {
        $curlOptions = $this->filterCurlOptions($curlOptions);

        $clientConfig = array_merge($this->createClientConfig(), [
            'curl' => $curlOptions,
        ]);

        return new Client($clientConfig);
    }

    protected function createClientConfig(): array
    {
        return array_merge($this->defaultConfig, [
            'handler' => $this->handlerStack,
        ]);
    }

    private function filterCurlOptions(array $curlOptions)
    {
        $definedCurlOptions = [];

        foreach ($curlOptions as $name => $value) {
            if (defined($name)) {
                $definedCurlOptions[constant($name)] = $value;
            }
        }

        return $definedCurlOptions;
    }
}
