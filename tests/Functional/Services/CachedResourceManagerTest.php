<?php

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Model\RequestIdentifier;
use App\Services\CachedResourceManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;
use webignition\HttpHeaders\Headers;

class CachedResourceManagerTest extends AbstractFunctionalTestCase
{
    /**
     * @var CachedResourceManager
     */
    private $cachedResourceManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    protected function setUp()
    {
        parent::setUp();

        $this->cachedResourceManager = self::$container->get(CachedResourceManager::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
    }

    public function testCreate()
    {
        $url = 'http://example.com/';
        $responseHeaders = new Headers([
            'content-type' => 'text/plain',
        ]);
        $body = 'cached response body';
        $requestIdentifier = new RequestIdentifier($url, new Headers());

        $cachedResource = $this->cachedResourceManager->create($requestIdentifier, $url, $responseHeaders, $body);

        $this->assertInstanceOf(CachedResource::class, $cachedResource);
        $this->assertEquals((string) $requestIdentifier, $cachedResource->getRequestHash());
        $this->assertEquals($url, $cachedResource->getUrl());
        $this->assertEquals($responseHeaders, $cachedResource->getHeaders());
        $this->assertEquals($body, $cachedResource->getBody());
        $this->assertNotNull($cachedResource->getLastStored());
        $this->assertInstanceOf(\DateTime::class, $cachedResource->getLastStored());
    }

    public function testUpdate()
    {
        $url = 'http://example.com/';
        $responseHeaders = new Headers([
            'content-type' => 'text/plain',
        ]);
        $body = 'cached response body';
        $requestIdentifier = new RequestIdentifier($url, new Headers());

        $cachedResource = $this->cachedResourceManager->create($requestIdentifier, $url, $responseHeaders, $body);

        $currentLastStored = $cachedResource->getLastStored();

        $this->cachedResourceManager->update($cachedResource);

        $this->assertNotSame($currentLastStored, $cachedResource->getLastStored());
    }

    public function testFindNotFound()
    {
        $this->assertNull($this->cachedResourceManager->find('foo'));
    }

    public function testFindHasFound()
    {
        $url = 'http://example.com/';
        $responseHeaders = new Headers([
            'content-type' => 'text/plain',
        ]);
        $body = 'cached response body';
        $requestIdentifier = new RequestIdentifier($url, new Headers());

        $this->cachedResourceManager->create($requestIdentifier, $url, $responseHeaders, $body);

        $this->entityManager->clear();

        $foundCachedResource = $this->cachedResourceManager->find($requestIdentifier);

        $this->assertInstanceOf(CachedResource::class, $foundCachedResource);
        $this->assertEquals((string) $requestIdentifier, $foundCachedResource->getRequestHash());
        $this->assertEquals($url, $foundCachedResource->getUrl());
        $this->assertEquals($responseHeaders, $foundCachedResource->getHeaders());
        $this->assertEquals($body, $foundCachedResource->getBody());
    }
}
