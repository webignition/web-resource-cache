<?php

namespace App\Tests\Unit\Services;

use App\Exception\HttpTransportException;
use App\Model\RetrieveRequest;
use App\Services\ResourceRetriever;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ResourceRetrieverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws HttpTransportException
     */
    public function testRetrieveHttpClientTimeoutOptionIsSet()
    {
        $response = \Mockery::mock(ResponseInterface::class);

        $timeout = 10;
        $httpClient = \Mockery::mock(Client::class);

        $httpClient
            ->shouldReceive('send')
            ->withArgs(function (RequestInterface $request, array $options) use ($timeout) {
                $this->assertArrayHasKey('timeout', $options);
                $this->assertEquals($timeout, $options['timeout']);

                return true;
            })
            ->andReturn($response);

        $resourceRetriever = new ResourceRetriever($httpClient, $timeout);

        $retrieveRequest = new RetrieveRequest('request_hash', 'http://example.com');

        $resourceRetriever->retrieve($retrieveRequest);
    }
}
