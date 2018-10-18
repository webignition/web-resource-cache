<?php

namespace App\Tests\Functional\Entity;

use App\Entity\RetrieveRequest;
use App\Model\Headers;
use App\Model\RequestIdentifier;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class RetrieveRequestTest extends AbstractFunctionalTestCase
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
     * @param array $callbackUrls
     */
    public function testCreate(string $url, Headers $headers, array $callbackUrls)
    {
        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl($url);
        $retrieveRequest->setHeaders($headers);

        foreach ($callbackUrls as $callbackUrl) {
            $retrieveRequest->addCallbackUrl($callbackUrl);
        }

        $retrieveRequest->setHash(new RequestIdentifier($url, $headers));

        $this->assertNull($retrieveRequest->getId());
        $this->assertEquals($url, $retrieveRequest->getUrl());
        $this->assertEquals($headers, $retrieveRequest->getHeaders());
        $this->assertEquals($callbackUrls, $retrieveRequest->getCallbackUrls());
        $this->assertRegExp('/[a-z0-9]{32}/', $retrieveRequest->getHash());

        $this->entityManager->persist($retrieveRequest);
        $this->entityManager->flush();

        $this->assertNotNull($retrieveRequest->getId());

        $id = $retrieveRequest->getId();
        $hash = $retrieveRequest->getHash();

        $this->entityManager->clear();

        $retrievedRetrieveRequest = $this->entityManager->find(RetrieveRequest::class, $id);

        $this->assertEquals($id, $retrievedRetrieveRequest->getId());
        $this->assertEquals($url, $retrievedRetrieveRequest->getUrl());
        $this->assertEquals($callbackUrls, $retrievedRetrieveRequest->getCallbackUrls());
        $this->assertEquals($headers, $retrievedRetrieveRequest->getHeaders());
        $this->assertEquals($hash, $retrievedRetrieveRequest->getHash());
    }

    public function createDataProvider(): array
    {
        return [
            'single callback url, no headers' => [
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'callbackUrls' => [
                    'http://foo.example.com/callback',
                ],
            ],
            'multiple callback urls, no headers' => [
                'url' => 'http://example.com/',
                'headers' => new Headers(),
                'callbackUrls' => [
                    'http://bar.example.com/callback',
                    'http://foo.example.com/callback',
                ],
            ],
            'single callback url, has headers' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                ]),
                'callbackUrls' => [
                    'http://foo.example.com/callback',
                ],
            ],
            'multiple callback urls, has headers' => [
                'url' => 'http://example.com/',
                'headers' => new Headers([
                    'foo' => 'bar',
                ]),
                'callbackUrls' => [
                    'http://bar.example.com/callback',
                    'http://foo.example.com/callback',
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateCallbackUrlsDataProvider
     *
     * @param array $callbackUrls
     * @param array $additionalCallbackUrls
     * @param array $expectedCallbackUrls
     */
    public function testUpdateCallbackUrls(
        array $callbackUrls,
        array $additionalCallbackUrls,
        array $expectedCallbackUrls
    ) {
        $url = 'http://example.com/';
        $headers = new Headers();

        $retrieveRequest = $this->createRetrieveRequest($url, $headers, $callbackUrls);

        $this->assertNotNull($retrieveRequest->getId());

        foreach ($additionalCallbackUrls as $callbackUrl) {
            $retrieveRequest->addCallbackUrl($callbackUrl);
        }

        $this->assertEquals($expectedCallbackUrls, $retrieveRequest->getCallbackUrls());

        $id = $retrieveRequest->getId();

        $this->entityManager->persist($retrieveRequest);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $retrievedRetrieveRequest = $this->entityManager->find(RetrieveRequest::class, $id);

        $this->assertEquals($id, $retrievedRetrieveRequest->getId());
        $this->assertEquals($expectedCallbackUrls, $retrievedRetrieveRequest->getCallbackUrls());
    }

    public function updateCallbackUrlsDataProvider(): array
    {
        return [
            'no additional callback urls' => [
                'callbackUrls' => [
                    'http://foo.example.com/callback',
                ],
                'additionalCallbackUrls' => [],
                'expectedCallbackUrls' => [
                    'http://foo.example.com/callback',
                ],
            ],
            'has additional callback urls, no duplicates' => [
                'callbackUrls' => [
                    'http://foo.example.com/callback',
                ],
                'additionalCallbackUrls' => [
                    'http://bar.example.com/callback',
                    'http://foobar.example.com/callback',
                ],
                'expectedCallbackUrls' => [
                    'http://foo.example.com/callback',
                    'http://bar.example.com/callback',
                    'http://foobar.example.com/callback',
                ],
            ],
            'has additional callback urls, has duplicates' => [
                'callbackUrls' => [
                    'http://foo.example.com/callback',
                ],
                'additionalCallbackUrls' => [
                    'http://foo.example.com/callback',
                    'http://bar.example.com/callback',
                    'http://bar.example.com/callback',
                ],
                'expectedCallbackUrls' => [
                    'http://foo.example.com/callback',
                    'http://bar.example.com/callback',
                ],
            ],
        ];
    }

    public function testIncrementRetryCount()
    {
        $url = 'http://example.com';
        $headers = new Headers();
        $callbackUrls = ['http://foo.example.com/callback'];

        $retrieveRequest = $this->createRetrieveRequest($url, $headers, $callbackUrls);
        $this->assertSame(0, $retrieveRequest->getRetryCount());

        $retrieveRequest->incrementRetryCount();
        $this->assertSame(1, $retrieveRequest->getRetryCount());

        $retrieveRequest->incrementRetryCount();
        $this->assertSame(2, $retrieveRequest->getRetryCount());

        $retrieveRequest->incrementRetryCount();
        $this->assertSame(3, $retrieveRequest->getRetryCount());

        $id = $retrieveRequest->getId();

        $this->entityManager->persist($retrieveRequest);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $retrievedRetrieveRequest = $this->entityManager->find(RetrieveRequest::class, $id);
        $this->assertSame(3, $retrievedRetrieveRequest->getRetryCount());
    }


    public function testSetHeaders()
    {
        $originalHeaders = new Headers(['foo' => 'bar']);
        $newHeaders = new Headers(['fizz' => 'buzz']);

        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setHeaders($originalHeaders);

        $this->assertSame($originalHeaders->toArray(), $retrieveRequest->getHeaders()->toArray());

        $retrieveRequest->setHeaders($newHeaders);
        $this->assertSame($newHeaders->toArray(), $retrieveRequest->getHeaders()->toArray());
    }

    private function createRetrieveRequest(string $url, Headers $headers, array $callbackUrls): RetrieveRequest
    {
        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl($url);
        $retrieveRequest->setHeaders($headers);

        foreach ($callbackUrls as $callbackUrl) {
            $retrieveRequest->addCallbackUrl($callbackUrl);
        }

        $retrieveRequest->setHash(new RequestIdentifier($url, $headers));

        $this->entityManager->persist($retrieveRequest);
        $this->entityManager->flush();

        return $retrieveRequest;
    }
}
