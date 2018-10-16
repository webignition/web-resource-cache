<?php

namespace App\Tests\Functional\Entity;

use App\Entity\GetRequest;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class GetRequestTest extends AbstractFunctionalTestCase
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

        $getRequest = new GetRequest();
        $getRequest->setUrl($url);

        foreach ($callbackUrls as $callbackUrl) {
            $getRequest->addCallbackUrl($callbackUrl);
        }

        $this->assertNull($getRequest->getId());
        $this->assertEquals($url, $getRequest->getUrl());
        $this->assertEquals($callbackUrls, $getRequest->getCallbackUrls());

        $entityManager->persist($getRequest);
        $entityManager->flush();

        $this->assertNotNull($getRequest->getId());

        $id = $getRequest->getId();

        $entityManager->clear();

        $retrievedGetRequest = $entityManager->find(GetRequest::class, $id);

        $this->assertEquals($id, $retrievedGetRequest->getId());
        $this->assertEquals($url, $retrievedGetRequest->getUrl());
        $this->assertEquals($callbackUrls, $retrievedGetRequest->getCallbackUrls());
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
     * @param array $getRequestData
     * @param array $additionalCallbackUrls
     * @param array $expectedCallbackUrls
     */
    public function testUpdate(array $getRequestData, array $additionalCallbackUrls, array $expectedCallbackUrls)
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $getRequest = $this->createGetRequest($getRequestData['url'], $getRequestData['callbackUrls']);

        $this->assertNotNull($getRequest->getId());

        foreach ($additionalCallbackUrls as $callbackUrl) {
            $getRequest->addCallbackUrl($callbackUrl);
        }

        $this->assertEquals($expectedCallbackUrls, $getRequest->getCallbackUrls());

        $id = $getRequest->getId();

        $entityManager->persist($getRequest);
        $entityManager->flush();
        $entityManager->clear();

        $retrievedGetRequest = $entityManager->find(GetRequest::class, $id);

        $this->assertEquals($id, $retrievedGetRequest->getId());
        $this->assertEquals($expectedCallbackUrls, $retrievedGetRequest->getCallbackUrls());
    }

    public function updateDataProvider(): array
    {
        return [
            'no additional callback urls' => [
                'getRequestData' => [
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
                'getRequestData' => [
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
                'getRequestData' => [
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

    private function createGetRequest(string $url, array $callbackUrls): GetRequest
    {
        $getRequest = new GetRequest();
        $getRequest->setUrl($url);

        foreach ($callbackUrls as $callbackUrl) {
            $getRequest->addCallbackUrl($callbackUrl);
        }

        /* @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $entityManager->persist($getRequest);
        $entityManager->flush();

        return $getRequest;
    }
}
