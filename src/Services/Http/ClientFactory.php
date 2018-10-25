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

    public function create(array $curlOptions, ?HandlerStack $handlerStack = null): Client
    {
        $curlOptions = $this->filterCurlOptions($curlOptions);

        $clientConfig = array_merge($this->createClientConfig(), [
            'curl' => $curlOptions,
            'handler' => $handlerStack,
        ]);

        return new Client($clientConfig);
    }

    protected function createClientConfig(): array
    {
        return array_merge($this->defaultConfig);
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
