<?php

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Model\RetrieveRequest;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
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

    /**
     * @var CachedResourceFactory
     */
    private $cachedResourceFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->cachedResourceManager = self::$container->get(CachedResourceManager::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
    }

    public function testUpdate()
    {
        $cachedResource = $this->createCachedResource('request_hash', 'http://example.com/', [], '');
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

        $requestHash = 'request_hash';

        $cachedResource = $this->createCachedResource($requestHash, $url, $responseHeaders->toArray(), $body);
        $this->cachedResourceManager->update($cachedResource);

        $this->entityManager->clear();

        $foundCachedResource = $this->cachedResourceManager->find($requestHash);

        $this->assertInstanceOf(CachedResource::class, $foundCachedResource);
        $this->assertEquals($requestHash, $foundCachedResource->getRequestHash());
        $this->assertEquals($url, $foundCachedResource->getUrl());
        $this->assertEquals($responseHeaders, $foundCachedResource->getHeaders());
        $this->assertEquals($body, $foundCachedResource->getBody());
    }

    private function createCachedResource(
        string $requestHash,
        string $url,
        array $httpResponseHeaders,
        string $httpResponseBody
    ): CachedResource {
        $retrieveRequest = new RetrieveRequest($requestHash, $url);
        $httpResponse = new Response(200, $httpResponseHeaders, $httpResponseBody);

        return $this->cachedResourceFactory->create(
            $retrieveRequest->getRequestHash(),
            $retrieveRequest->getUrl(),
            $httpResponse
        );
    }
}
