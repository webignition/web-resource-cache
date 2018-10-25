<?php

namespace App\Services;

use App\Model\Response\ResponseInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class ResponseSender
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function send(string $url, ResponseInterface $response)
    {
        try {
            $this->httpClient->send(new Request(
                'POST',
                $url,
                ['content-type' => 'application/json'],
                json_encode($response)
            ));
        } catch (GuzzleException $e) {
            return false;
        }

        return true;
    }
}
