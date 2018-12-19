<?php

namespace App\Model;

class RequestParameters
{
    /**
     * @var array
     */
    private $parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function getCookieParameters(): CookieParameters
    {
        $cookieParameters = $this->parameters['cookies'] ?? [
            'domain' => '',
            'path' => '',
        ];

        $domain = $cookieParameters['domain'] ?? '';
        $path = $cookieParameters['path'] ?? '';

        return new CookieParameters($domain, $path);
    }

    public function getAuthorizationParameters(): AuthorizationParameters
    {
        $authorizationParameters = $this->parameters['http-authorization'] ?? [
                'host' => '',
            ];

        $host = $authorizationParameters['host'] ?? '';

        return new AuthorizationParameters($host);
    }
}
