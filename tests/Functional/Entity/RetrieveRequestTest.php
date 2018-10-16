<?php

namespace App\Tests\Functional\Entity;

use App\Entity\RetrieveRequest;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class RetrieveRequestTest extends AbstractFunctionalTestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $url
     * @param array $callbackUrls
     */
    public function testCreate(string $url, array $callbackUrls)
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl($url);

        foreach ($callbackUrls as $callbackUrl) {
            $retrieveRequest->addCallbackUrl($callbackUrl);
        }

        $this->assertNull($retrieveRequest->getId());
        $this->assertEquals($url, $retrieveRequest->getUrl());
        $this->assertEquals($callbackUrls, $retrieveRequest->getCallbackUrls());

        $entityManager->persist($retrieveRequest);
        $entityManager->flush();

        $this->assertNotNull($retrieveRequest->getId());

        $id = $retrieveRequest->getId();

        $entityManager->clear();

        $retrievedRetrieveRequest = $entityManager->find(RetrieveRequest::class, $id);

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
                    'http://foo.example.com/callback',
                    'http://bar.example.com/callback',
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateDataProvider
     *
     * @param array $retrieveRequestData
     * @param array $additionalCallbackUrls
     * @param array $expectedCallbackUrls
     */
    public function testUpdate(array $retrieveRequestData, array $additionalCallbackUrls, array $expectedCallbackUrls)
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);

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

        $entityManager->persist($retrieveRequest);
        $entityManager->flush();
        $entityManager->clear();

        $retrievedRetrieveRequest = $entityManager->find(RetrieveRequest::class, $id);

        $this->assertEquals($id, $retrievedRetrieveRequest->getId());
        $this->assertEquals($expectedCallbackUrls, $retrievedRetrieveRequest->getCallbackUrls());
    }

    public function updateDataProvider(): array
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

    private function createRetrieveRequest(string $url, array $callbackUrls): RetrieveRequest
    {
        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl($url);

        foreach ($callbackUrls as $callbackUrl) {
            $retrieveRequest->addCallbackUrl($callbackUrl);
        }

        /* @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $entityManager->persist($retrieveRequest);
        $entityManager->flush();

        return $retrieveRequest;
    }
}
