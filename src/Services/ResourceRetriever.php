<?php

namespace App\Services;

use App\Entity\RetrieveRequest;
use App\Exception\TransportException;
use App\Model\RequestResponse;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;

class ResourceRetriever
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param RetrieveRequest $retrieveRequest
     *
     * @return RequestResponse
     *
     * @throws TransportException
     */
    public function retrieve(RetrieveRequest $retrieveRequest): RequestResponse
    {
        $request = new Request('GET', $retrieveRequest->getUrl());

        $requestUri = $request->getUri();
        $response = null;

        try {
            $response = $this->httpClient->send($request, [
                'on_stats' => function (TransferStats $stats) use (&$requestUri) {
                    if ($stats->hasResponse()) {
                        $requestUri = $stats->getEffectiveUri();
                    }
                },
            ]);
        } catch (BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();
        } catch (RequestException $requestException) {
            throw new TransportException($request, $requestException);
        } catch (GuzzleException $guzzleException) {
            throw new TransportException(
                $request,
                new RequestException($guzzleException->getMessage(), $request, null, $guzzleException)
            );
        }

        $request = $request->withUri($requestUri);

        return new RequestResponse($request, $response);
    }
}