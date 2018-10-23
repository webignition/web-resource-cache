<?php

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Model\Headers;
use App\Model\RequestIdentifier;
use App\Services\CachedResourceManager;
use App\Tests\Functional\AbstractFunctionalTestCase;

class CachedResourceManagerTest extends AbstractFunctionalTestCase
{
    /**
     * @var CachedResourceManager
     */
    private $cachedResourceCreator;

    protected function setUp()
    {
        parent::setUp();

        $this->cachedResourceCreator = self::$container->get(CachedResourceManager::class);
    }

    public function testCreate()
    {
        $url = 'http://example.com/';
        $responseHeaders = new Headers([
            'content-type' => 'text/plain',
        ]);
        $body = 'cached response body';
        $requestIdentifier = new RequestIdentifier($url, new Headers());

        $cachedResource = $this->cachedResourceCreator->create($requestIdentifier, $url, $responseHeaders, $body);

        $this->assertInstanceOf(CachedResource::class, $cachedResource);
        $this->assertNotNull($cachedResource->getId());
        $this->assertEquals((string) $requestIdentifier, $cachedResource->getRequestHash());
        $this->assertEquals($url, $cachedResource->getUrl());
        $this->assertEquals($responseHeaders, $cachedResource->getHeaders());
        $this->assertEquals($body, $cachedResource->getBody());
        $this->assertNotNull($cachedResource->getLastStored());
        $this->assertInstanceOf(\DateTime::class, $cachedResource->getLastStored());
    }
}
