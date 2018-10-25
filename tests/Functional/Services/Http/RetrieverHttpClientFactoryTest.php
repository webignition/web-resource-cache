<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\RetrieverHttpClientFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;
use GuzzleHttp\Client;

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
    }
}
