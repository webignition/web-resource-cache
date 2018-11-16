<?php

namespace App\Tests\Functional\Services\Http;

use App\Tests\Functional\AbstractFunctionalTestCase;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJarInterface;

class HttpClientTest extends AbstractFunctionalTestCase
{
    /**
     * @dataProvider getServiceDataProvider
     *
     * @param $serviceId
     */
    public function testGetService(string $serviceId)
    {
        $this->assertInstanceOf(HttpClient::class, self::$container->get($serviceId));
    }

    public function getServiceDataProvider(): array
    {
        return [
            'sender' => [
                'serviceId' => 'async_http_retriever.http.client.sender',
            ],
            'retriever' => [
                'serviceId' => 'async_http_retriever.http.client.retriever',
            ],
        ];
    }

    /**
     * @dataProvider configDataProvider
     *
     * @param string $serviceId
     * @param string $expectedSenderServiceId
     * @param array $expectedConfig
     */
    public function testConfig(string $serviceId, string $expectedSenderServiceId, array $expectedConfig)
    {
        /* @var HttpClient $httpClient */
        $httpClient = self::$container->get($serviceId);
        $httpClientConfig = $httpClient->getConfig();

        $httpClientCurlOptions = $httpClientConfig['curl'];

        $curlOptionsParameters = self::$container->getParameter('curl_options');

        foreach ($curlOptionsParameters as $name => $value) {
            $nameConstantValue = constant($name);

            $this->assertArrayHasKey($nameConstantValue, $httpClientCurlOptions);
            $this->assertSame($httpClientCurlOptions[$nameConstantValue], $value);
        }

        $this->assertFalse($httpClientConfig['verify']);
        $this->assertEquals(
            self::$container->get($expectedSenderServiceId),
            $httpClientConfig['handler']
        );

        foreach ($expectedConfig as $key => $value) {
            $this->assertArrayHasKey($key, $httpClientConfig);

            if (is_string($value) && preg_match('/^service:/', $value)) {
                $expectedServiceId = str_replace('service:', '', $value);

                $value = self::$container->get($expectedServiceId);
            }

            $this->assertEquals($value, $httpClientConfig[$key]);
        }
    }

    public function configDataProvider(): array
    {
        return [
            'sender' => [
                'serviceId' => 'async_http_retriever.http.client.sender',
                'expectedSenderServiceId' => 'async_http_retriever.http.handler_stack.sender',
                'expectedConfig' => [
                    'cookies' => false,
                ],
            ],
            'retriever' => [
                'serviceId' => 'async_http_retriever.http.client.retriever',
                'expectedSenderServiceId' => 'async_http_retriever.http.handler_stack.retriever',
                'expectedConfig' => [
                    'cookies' => 'service:' . CookieJarInterface::class,
                    'timeout' => 30,
                ],
            ],
        ];
    }
}
