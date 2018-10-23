<?php

namespace App\Tests\Functional\Entity;

use App\Entity\CachedResource;
use App\Model\Headers;
use App\Model\RequestIdentifier;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class CachedResourceTest extends AbstractFunctionalTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $url
     * @param Headers $headers
     * @param string $body
     * @param RequestIdentifier $requestIdentifier
     * @param \DateTime $lastStored
     */
    public function testCreate(
        string $url,
        Headers $headers,
        string $body,
        RequestIdentifier $requestIdentifier,
        \DateTime $lastStored
    ) {
        $resource = new CachedResource();

        $this->assertInstanceOf(\DateTime::class, $resource->getLastStored());
        $this->assertNotEquals($lastStored, $resource->getLastStored());

        $resource->setUrl($url);
        $resource->setHeaders($headers);
        $resource->setBody($body);
        $resource->setRequestHash($requestIdentifier);
        $resource->setLastStored($lastStored);

        $this->assertEquals($url, $resource->getUrl());
        $this->assertEquals($headers, $resource->getHeaders());
        $this->assertEquals($body, $resource->getBody());
        $this->assertEquals((string) $requestIdentifier, $resource->getRequestHash());
        $this->assertEquals($lastStored, $resource->getLastStored());

        $this->entityManager->persist($resource);
        $this->entityManager->flush();

        $hash = $resource->getRequestHash();

        $this->entityManager->clear();

        /* @var CachedResource $retrievedResource */
        $retrievedResource = $this->entityManager->find(CachedResource::class, $hash);

        $this->assertEquals($url, $retrievedResource->getUrl());
        $this->assertEquals($headers, $retrievedResource->getHeaders());
        $this->assertEquals($body, $retrievedResource->getBody());
        $this->assertEquals((string) $requestIdentifier, $retrievedResource->getRequestHash());
        $this->assertEquals($lastStored, $retrievedResource->getLastStored());
    }

    public function createDataProvider(): array
    {
        return [
            'empty headers, empty body' => [
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'body' => '',
                'requestIdentifier' => new RequestIdentifier('http://example.com', new Headers()),
                'lastStored' => new \DateTime('2018-10-18 11:41'),
            ],
            'has headers, empty body' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                ]),
                'body' => '',
                'requestIdentifier' => new RequestIdentifier('http://example.com', new Headers()),
                'lastStored' => new \DateTime('2018-10-18 12:41'),
            ],
            'empty headers, has body' => [
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'body' => 'body content',
                'requestIdentifier' => new RequestIdentifier('http://example.com', new Headers()),
                'lastStored' => new \DateTime('2018-10-18 13:41'),
            ],
            'has headers, has body' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                ]),
                'body' => 'body content',
                'requestIdentifier' => new RequestIdentifier('http://example.com', new Headers()),
                'lastStored' => new \DateTime('2018-10-18 14:41'),
            ],
        ];
    }
}
