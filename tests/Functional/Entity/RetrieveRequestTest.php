<?php

namespace App\Tests\Functional\Entity;

use App\Entity\RetrieveRequest;
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
     * @param array $callbackUrls
     */
    public function testCreate(string $url, array $callbackUrls)
    {
        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl($url);

        foreach ($callbackUrls as $callbackUrl) {
            $retrieveRequest->addCallbackUrl($callbackUrl);
        }

        $this->assertNull($retrieveRequest->getId());
        $this->assertEquals($url, $retrieveRequest->getUrl());
        $this->assertEquals($callbackUrls, $retrieveRequest->getCallbackUrls());

        $this->entityManager->persist($retrieveRequest);
        $this->entityManager->flush();

        $this->assertNotNull($retrieveRequest->getId());

        $id = $retrieveRequest->getId();

        $this->entityManager->clear();

        $retrievedRetrieveRequest = $this->entityManager->find(RetrieveRequest::class, $id);

        $this->assertEquals($id, $retrievedRetrieveRequest->getId());
        $this->assertEquals($url, $retrievedRetrieveRequest->getUrl());
        $this->assertEquals($callbackUrls, $retrievedRetrieveRequest->getCallbackUrls());
    }

    public function createDataProvider(): array
    {
        return [
            'url, single callback url' => [
                'url' => 'http://example.com/',
                'callbackUrls' => [
                    'http://foo.example.com/callback',
                ],
            ],
            'url, multiple callback urls' => [
                'url' => 'http://example.com/',
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
     * @param array $retrieveRequestData
     * @param array $additionalCallbackUrls
     * @param array $expectedCallbackUrls
     */
    public function testUpdateCallbackUrls(
        array $retrieveRequestData,
        array $additionalCallbackUrls,
        array $expectedCallbackUrls
    ) {
        $retrieveRequest = $this->createRetrieveRequest(
            $retrieveRequestData['url'],
            $retrieveRequestData['callbackUrls']
        );

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
                'retrieveRequestData' => [
                    'url' => 'http://example.com/',
                    'callbackUrls' => [
                        'http://foo.example.com/callback',
                    ],
                ],
                'additionalCallbackUrls' => [],
                'expectedCallbackUrls' => [
                    'http://foo.example.com/callback',
                ],
            ],
            'has additional callback urls, no duplicates' => [
                'retrieveRequestData' => [
                    'url' => 'http://example.com/',
                    'callbackUrls' => [
                        'http://foo.example.com/callback',
                    ],
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
                'retrieveRequestData' => [
                    'url' => 'http://example.com/',
                    'callbackUrls' => [
                        'http://foo.example.com/callback',
                    ],
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
        $retrieveRequest = $this->createRetrieveRequest('http://example.com', ['http://foo.example.com/callback']);
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

    /**
     * @dataProvider setHeadersDataProvider
     *
     * @param array $existingHeaders
     * @param array $headers
     * @param array $expectedHeaders
     */
    public function testSetHeaders(array $existingHeaders, array $headers, array $expectedHeaders)
    {
        $retrieveRequest = $this->createRetrieveRequest(
            'http://example.com',
            [
                'http://callback.example.com',
            ],
            $existingHeaders
        );

        $retrieveRequest->setHeaders($headers);

        $this->assertEquals($expectedHeaders, $retrieveRequest->getHeaders());
    }

    public function setHeadersDataProvider(): array
    {
        return [
            'no existing headers, no new headers' => [
                'existingHeaders' => [],
                'headers' => [],
                'expectedHeaders' => [],
            ],
            'has existing headers, no new headers' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'headers' => [],
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
            'no existing headers, has new headers' => [
                'existingHeaders' => [],
                'headers' => [
                    'foo' => 'bar',
                ],
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
            'header name is converted to lowercase' => [
                'existingHeaders' => [
                    'FOO' => 'bar',
                ],
                'headers' => [],
                'expectedHeaders' => [
                    'foo' => 'bar',
                ],
            ],
            'has existing headers, has new headers, no overwrite' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                ],
                'headers' => [
                    'fizz' => 'buzz',
                ],
                'expectedHeaders' => [
                    'foo' => 'bar',
                    'fizz' => 'buzz',
                ],
            ],
            'has existing headers, has new headers, new headers overwrite existing headers' => [
                'existingHeaders' => [
                    'foo' => 'bar',
                    'fizz' => 'buzz',
                ],
                'headers' => [
                    'fizz' => 'bee',
                ],
                'expectedHeaders' => [
                    'foo' => 'bar',
                    'fizz' => 'bee',
                ],
            ],
        ];
    }

    public function testUpdateHash()
    {
        $retrieveRequest = new RetrieveRequest();
        $this->assertEquals('d751713988987e9331980363e24189ce', $retrieveRequest->getHash());

        $retrieveRequest->setUrl('http://foo.example.com/');
        $this->assertEquals('cc2957092739eab04d826b3985af594b', $retrieveRequest->getHash());

        $retrieveRequest->addCallbackUrl('http://callback.example.com/');
        $this->assertEquals('cc2957092739eab04d826b3985af594b', $retrieveRequest->getHash());

        $retrieveRequest->incrementRetryCount();
        $this->assertEquals('cc2957092739eab04d826b3985af594b', $retrieveRequest->getHash());

        $retrieveRequest->setHeaders(['foo' => 'bar']);
        $this->assertEquals('c1e5b074eb7e4898841543edfbf7c28b', $retrieveRequest->getHash());
    }

    public function testHeaderSetOrderDoesNotAffectHash()
    {
        $retrieveRequest1 = new RetrieveRequest();
        $retrieveRequest1->setHeader('foo', 'bar');
        $retrieveRequest1->setHeader('fizz', 'buzz');

        $retrieveRequest2 = new RetrieveRequest();
        $retrieveRequest2->setHeader('fizz', 'buzz');
        $retrieveRequest2->setHeader('foo', 'bar');

        $this->assertEquals($retrieveRequest1->getHash(), $retrieveRequest2->getHash());
    }

    /**
     * @dataProvider setHeaderValidValueTypeDataProvider
     *
     * @param string|int $value
     */
    public function testSetHeaderValidValueType($value)
    {
        $retrieveRequest = new RetrieveRequest();

        $this->assertTrue($retrieveRequest->setHeader('foo', $value));
        $this->assertEquals(
            [
                'foo' => $value,
            ],
            $retrieveRequest->getHeaders()
        );
    }

    public function setHeaderValidValueTypeDataProvider(): array
    {
        return [
            'string' => [
                'value' => 'bar',
            ],
            'integer' => [
                'value' => 12,
            ],
        ];
    }

    /**
     * @dataProvider setHeaderInvalidValueTypeDataProvider
     *
     * @param mixed $value
     */
    public function testSetHeaderInvalidValueType($value)
    {
        $retrieveRequest = new RetrieveRequest();

        $this->assertFalse($retrieveRequest->setHeader('foo', $value));
    }

    public function setHeaderInvalidValueTypeDataProvider(): array
    {
        return [
            'boolean' => [
                'value' => true,
            ],
            'array' => [
                'value' => [1, 2, 3],
            ],
            'object' => [
                'value' => (object)[1, 2, 3],
            ],
        ];
    }

    private function createRetrieveRequest(
        string $url,
        array $callbackUrls,
        array $existingHeaders = []
    ): RetrieveRequest {
        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl($url);

        foreach ($callbackUrls as $callbackUrl) {
            $retrieveRequest->addCallbackUrl($callbackUrl);
        }

        $retrieveRequest->setHeaders($existingHeaders);

        $this->entityManager->persist($retrieveRequest);
        $this->entityManager->flush();

        return $retrieveRequest;
    }
}
