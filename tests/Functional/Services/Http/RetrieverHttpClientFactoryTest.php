<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\RetrieverHttpClientFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\HandlerStack;

class RetrieverHttpClientFactoryTest extends AbstractFunctionalTestCase
{
    /**
     * @var RetrieverHttpClientFactory
     */
    private $retrieverHttpClientFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->retrieverHttpClientFactory = self::$container->get(RetrieverHttpClientFactory::class);
    }

    public function testCreate()
    {
        $httpClient = $this->retrieverHttpClientFactory->create();

        $this->assertInstanceOf(Client::class, $httpClient);
        $this->assertEquals(self::$container->get('web_resource_cache.http.client.retriever'), $httpClient);

        $httpClientCurlOptions = $httpClient->getConfig('curl');

        $curlOptionsParameters = self::$container->getParameter('curl_options');

        foreach ($curlOptionsParameters as $name => $value) {
            $nameConstantValue = constant($name);

            $this->assertArrayHasKey($nameConstantValue, $httpClientCurlOptions);
            $this->assertSame($httpClientCurlOptions[$nameConstantValue], $value);
        }

        $this->assertFalse($httpClient->getConfig('verify'));
        $this->assertEquals(
            self::$container->get('web_resource_cache.http.handler_stack.retriever'),
            $httpClient->getConfig('handler')
        );
        $this->assertEquals(self::$container->get(CookieJarInterface::class), $httpClient->getConfig('cookies'));
    }
}
