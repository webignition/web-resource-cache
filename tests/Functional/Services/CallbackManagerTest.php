<?php

namespace App\Tests\Functional\Services;

use App\Entity\Callback;
use App\Services\CallbackFactory;
use App\Services\CallbackManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class CallbackManagerTest extends AbstractFunctionalTestCase
{
    /**
     * @var CallbackManager
     */
    private $callbackManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CallbackFactory
     */
    private $callbackFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->callbackManager = self::$container->get(CallbackManager::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->callbackFactory = self::$container->get(CallbackFactory::class);

        $existingCallbacks = [
            [
                'requestHash' => 'foo',
                'url' => 'http://foo1.example.com',
            ],
            [
                'requestHash' => 'request-hash',
                'url' => 'http://callback1.example.com',
            ],
            [
                'requestHash' => 'foo',
                'url' => 'http://foo2.example.com',
            ],
        ];

        foreach ($existingCallbacks as $existingCallback) {
            $callback = $this->callbackFactory->create(
                $existingCallback['requestHash'],
                $existingCallback['url'],
                false
            );

            $this->callbackManager->persist($callback);
        }
    }

    /**
     * @dataProvider findByRequestHashDataProvider
     *
     * @param string $requestHash
     * @param array $expectedCallUrls
     */
    public function testFindByRequestHash(string $requestHash, array $expectedCallUrls)
    {
        $callbacks = $this->callbackManager->findByRequestHash($requestHash);

        $callbackUrls = [];
        foreach ($callbacks as $callback) {
            $callbackUrls[] = $callback->getUrl();
        }

        $this->assertEquals($expectedCallUrls, $callbackUrls);
    }

    public function findByRequestHashDataProvider(): array
    {
        return [
            'none matching' => [
                'requestHash' => 'bar',
                'expectedCallUrls' => [],
            ],
            'one matching' => [
                'requestHash' => 'request-hash',
                'expectedCallUrls' => [
                    'http://callback1.example.com',
                ],
            ],
            'many matching' => [
                'requestHash' => 'foo',
                'expectedCallUrls' => [
                    'http://foo1.example.com',
                    'http://foo2.example.com',
                ],
            ],
        ];
    }

    /**
     * @dataProvider findByRequestHashAndUrlDataProvider
     *
     * @param string $requestHash
     * @param string $url
     * @param bool $expectedIsFound
     */
    public function testFindByRequestHashAndUrl(string $requestHash, string $url, bool $expectedIsFound)
    {
        $callback = $this->callbackManager->findByRequestHashAndUrl($requestHash, $url);

        $this->assertEquals($expectedIsFound, $callback instanceof Callback);
    }

    public function findByRequestHashAndUrlDataProvider(): array
    {
        return [
            'no match' => [
                'requestHash' => 'foo',
                'url' => 'http://callback1.example.com',
                'expectedIsFound' => false,
            ],
            'match' => [
                'requestHash' => 'request-hash',
                'url' => 'http://callback1.example.com',
                'expectedIsFound' => true,
            ],
        ];
    }
}
