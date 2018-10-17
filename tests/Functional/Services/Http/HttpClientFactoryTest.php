<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\HttpClientFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\HandlerStack;

class HttpClientFactoryTest extends AbstractFunctionalTestCase
{
    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->httpClientFactory = self::$container->get(HttpClientFactory::class);
    }

    public function testCreate()
    {
        $httpClient = $this->httpClientFactory->create();

        $this->assertInstanceOf(Client::class, $httpClient);
        $this->assertEquals(self::$container->get(Client::class), $httpClient);

        $httpClientCurlOptions = $httpClient->getConfig('curl');

        $curlOptionsParameters = self::$container->getParameter('curl_options');

        foreach ($curlOptionsParameters as $name => $value) {
            $nameConstantValue = constant($name);

            $this->assertArrayHasKey($nameConstantValue, $httpClientCurlOptions);
            $this->assertSame($httpClientCurlOptions[$nameConstantValue], $value);
        }

        $this->assertFalse($httpClient->getConfig('verify'));
        $this->assertEquals(self::$container->get(HandlerStack::class), $httpClient->getConfig('handler'));
        $this->assertEquals(self::$container->get(CookieJarInterface::class), $httpClient->getConfig('cookies'));
    }
}
