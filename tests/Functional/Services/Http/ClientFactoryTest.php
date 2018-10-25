<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\ClientFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;
use GuzzleHttp\Client;

class ClientFactoryTest extends AbstractFunctionalTestCase
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->clientFactory = self::$container->get(ClientFactory::class);
    }

    public function testCreate()
    {
        $httpClient = $this->clientFactory->create();

        $this->assertInstanceOf(Client::class, $httpClient);
        $this->assertEquals(self::$container->get('web_resource_cache.http.client.sender'), $httpClient);
    }
}
