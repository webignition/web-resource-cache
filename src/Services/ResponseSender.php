<?php

namespace App\Services;

use App\Entity\Callback;
use App\Model\Response\ResponseInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

class ResponseSender
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CallbackResponseLogger
     */
    private $callbackResponseLogger;

    public function __construct(
        HttpClient $httpClient,
        LoggerInterface $logger,
        CallbackResponseLogger $callbackResponseLogger
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->callbackResponseLogger = $callbackResponseLogger;
    }

    public function send(Callback $callback, ResponseInterface $response)
    {
        $httpResponse = null;

        try {
            $httpResponse = $this->httpClient->send(new Request(
                'POST',
                $callback->getUrl(),
                ['content-type' => 'application/json'],
                json_encode($response)
            ));
        } catch (GuzzleException $guzzleException) {
            $this->logger->error('Callback failed', [
                'requestId' => $response->getRequestId(),
                'code' => $guzzleException->getCode(),
                'message' => $guzzleException->getMessage(),
            ]);

            return false;
        }

        if ($callback->getLogResponse()) {
            $this->callbackResponseLogger->log($response->getRequestId(), $httpResponse);
        }

        return true;
    }
}
