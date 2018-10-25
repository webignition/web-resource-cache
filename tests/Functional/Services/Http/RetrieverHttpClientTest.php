<?php

namespace App\Tests\Functional\Services\Http;

use App\Tests\Functional\AbstractFunctionalTestCase;
use GuzzleHttp\Client as HttpClient;

class RetrieverHttpClientTest extends AbstractFunctionalTestCase
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    protected function setUp()
    {
        parent::setUp();

        $this->httpClient = self::$container->get('web_resource_cache.http_client.retriever');
    }

    public function testGet()
    {
        $this->assertInstanceOf(HttpClient::class, $this->httpClient);
    }
}
