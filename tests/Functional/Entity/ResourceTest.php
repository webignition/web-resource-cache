<?php

namespace App\Tests\Functional\Entity;

use App\Entity\Resource;
use App\Model\Headers;
use App\Model\RequestIdentifier;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ResourceTest extends AbstractFunctionalTestCase
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
     */
    public function testCreate(string $url, Headers $headers, string $body, RequestIdentifier $requestIdentifier)
    {
        $resource = new Resource();
        $resource->setUrl($url);
        $resource->setHeaders($headers);
        $resource->setBody($body);
        $resource->setRequestHash($requestIdentifier);

        $this->assertNull($resource->getId());
        $this->assertEquals($url, $resource->getUrl());
        $this->assertEquals($headers, $resource->getHeaders());
        $this->assertEquals($body, $resource->getBody());
        $this->assertEquals((string) $requestIdentifier, $resource->getRequestHash());

        $this->entityManager->persist($resource);
        $this->entityManager->flush();

        $this->assertNotNull($resource->getId());

        $id = $resource->getId();

        $this->entityManager->clear();

        /* @var Resource $retrievedResource */
        $retrievedResource = $this->entityManager->find(Resource::class, $id);

        $this->assertEquals($id, $retrievedResource->getId());
        $this->assertEquals($url, $retrievedResource->getUrl());
        $this->assertEquals($headers, $retrievedResource->getHeaders());
        $this->assertEquals($body, $retrievedResource->getBody());
        $this->assertEquals((string) $requestIdentifier, $retrievedResource->getRequestHash());
    }

    public function createDataProvider(): array
    {
        return [
            'empty headers, empty body' => [
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'body' => '',
                'requestIdentifier' => new RequestIdentifier('http://example.com', new Headers()),
            ],
            'has headers, empty body' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                ]),
                'body' => '',
                'requestIdentifier' => new RequestIdentifier('http://example.com', new Headers()),
            ],
            'empty headers, has body' => [
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'body' => 'body content',
                'requestIdentifier' => new RequestIdentifier('http://example.com', new Headers()),
            ],
            'has headers, has body' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                ]),
                'body' => 'body content',
                'requestIdentifier' => new RequestIdentifier('http://example.com', new Headers()),
            ],
        ];
    }
}
