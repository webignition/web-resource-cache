<?php

namespace App\Services;

use App\Exception\HttpTransportException;
use App\Model\RequestResponse;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use webignition\HttpHeaders\Headers;

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
     * @param string $url
     * @param Headers $headers
     *
     * @return RequestResponse
     *
     * @throws HttpTransportException
     */
    public function retrieve(string $url, ?Headers $headers = null): RequestResponse
    {
        $headers = $headers ?? new Headers();

        $request = new Request('GET', $url, $headers->toArray());

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
