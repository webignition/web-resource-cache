<?php

namespace App\Services;

use App\Exception\HttpTransportException;
use App\Model\RequestResponse;
use App\Model\RetrieveRequest;
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

    /**
     * @var int
     */
    private $requestTimeout;

    public function __construct(HttpClient $httpClient, int $requestTimeout)
    {
        $this->httpClient = $httpClient;
        $this->requestTimeout = $requestTimeout;
    }

    /**
     * @param RetrieveRequest $retrieveRequest
     *
     * @return RequestResponse
     *
     * @throws HttpTransportException
     */
    public function retrieve(RetrieveRequest $retrieveRequest): RequestResponse
    {
        $request = new Request('GET', $retrieveRequest->getUrl(), $retrieveRequest->getHeaders()->toArray());

        $requestUri = $request->getUri();
        $response = null;

        try {
            $response = $this->httpClient->send($request, [
                'timeout' => $this->requestTimeout,
                'on_stats' => function (TransferStats $stats) use (&$requestUri) {
                    if ($stats->hasResponse()) {
                        $requestUri = $stats->getEffectiveUri();
                    }
                },
            ]);
        } catch (BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();
        } catch (RequestException $requestException) {
            throw new HttpTransportException($request, $requestException);
        } catch (GuzzleException $guzzleException) {
            throw new HttpTransportException(
                $request,
                new RequestException($guzzleException->getMessage(), $request, null, $guzzleException)
            );
        }

        $request = $request->withUri($requestUri);

        return new RequestResponse($request, $response);
    }
}
