<?php

namespace App\Services;

use App\Exception\HttpTransportException;
use App\Model\CookieParameters;
use App\Model\RequestParameters;
use App\Model\RequestResponse;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\TransferStats;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\HttpHeaders\Headers;

class ResourceRetriever
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var CookieJarInterface
     */
    private $cookieJar;

    /**
     * @var HttpAuthenticationMiddleware
     */
    private $httpAuthenticationMiddleware;

    public function __construct(
        HttpClient $httpClient,
        CookieJarInterface $cookieJar,
        HttpAuthenticationMiddleware $httpAuthenticationMiddleware
    ) {
        $this->httpClient = $httpClient;
        $this->cookieJar = $cookieJar;
        $this->httpAuthenticationMiddleware = $httpAuthenticationMiddleware;
    }

    /**
     * @param string $url
     * @param Headers $headers
     * @param RequestParameters $requestParameters
     *
     * @return RequestResponse
     *
     * @throws HttpTransportException
     */
    public function retrieve(string $url, Headers $headers, RequestParameters $requestParameters): RequestResponse
    {
        $cookieHeader = $headers->get('cookie');
        if ($cookieHeader) {
            $this->setCookies($cookieHeader[0], $requestParameters->getCookieParameters());
            $headers = $headers->withoutHeader('cookie');
        }

        $authorizationHeader = $headers->getLine('authorization');
        if ($authorizationHeader) {
            $uri = new Uri($url);

            $this->setAuthorization($authorizationHeader, $uri->getHost());
            $headers->withoutHeader('authorization');
        }

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

    private function setCookies(string $cookieHeader, CookieParameters $cookieParameters)
    {
        $nameValues = explode('; ', $cookieHeader);

        foreach ($nameValues as $nameValue) {
            $cookieData = [
                'Name' => null,
                'Value' => null,
                'Path' => $cookieParameters->getPath(),
                'Domain' => $cookieParameters->getDomain(),
            ];

            $nameValueParts = explode('=', $nameValue, 2);
            $expectedPartCount = 2;

            if ($expectedPartCount === count($nameValueParts)) {
                $cookieData['Name'] = $nameValueParts[0];
                $cookieData['Value'] = $nameValueParts[1];
            }

            $this->cookieJar->setCookie(new SetCookie($cookieData));
        }
    }

    private function setAuthorization(string $authorizationHeader, string $host)
    {
        $authorizationParts = explode(' ', $authorizationHeader, 2);
        $expectedPartCount = 2;

        if ($expectedPartCount === count($authorizationParts)) {
            $this->httpAuthenticationMiddleware->setType($authorizationParts[0]);
            $this->httpAuthenticationMiddleware->setCredentials($authorizationParts[1]);
            $this->httpAuthenticationMiddleware->setHost($host);
        } else {
            $this->httpAuthenticationMiddleware->clearType();
            $this->httpAuthenticationMiddleware->clearCredentials();
        }
    }
}
