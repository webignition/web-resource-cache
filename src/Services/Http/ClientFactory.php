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

    public function create(array $curlOptions, HandlerStack $handlerStack, array $config = []): Client
    {
        $curlOptions = $this->filterCurlOptions($curlOptions);

        $clientConfig = array_merge(
            $this->defaultConfig,
            [
                'curl' => $curlOptions,
                'handler' => $handlerStack,
            ],
            $config
        );

        return new Client($clientConfig);
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
