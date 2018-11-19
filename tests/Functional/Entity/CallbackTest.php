<?php

namespace App\Tests\Functional\Entity;

use App\Entity\Callback;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class CallbackTest extends AbstractFunctionalTestCase
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

    public function testDefaultLogResponse()
    {
        $callback = new Callback();

        $this->assertFalse($callback->getLogResponse());
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $url
     * @param string $requestHash
     * @param bool $logResponse
     */
    public function testCreate(string $url, string $requestHash, bool $logResponse)
    {
        $callback = new Callback();
        $callback->setUrl($url);
        $callback->setRequestHash($requestHash);
        $callback->setLogResponse($logResponse);

        $this->assertNull($callback->getId());
        $this->assertEquals($url, $callback->getUrl());
        $this->assertEquals($requestHash, $callback->getRequestHash());
        $this->assertEquals(0, $callback->getRetryCount());
        $this->assertEquals($logResponse, $callback->getLogResponse());
        $this->entityManager->persist($callback);
        $this->entityManager->flush();

        $this->assertNotNull($callback->getId());

        $id = $callback->getId();

        $this->entityManager->clear();

        $retrievedCallback = $this->entityManager->find(Callback::class, $id);

        $this->assertEquals($url, $retrievedCallback->getUrl());
        $this->assertEquals($requestHash, $retrievedCallback->getRequestHash());
        $this->assertEquals(0, $retrievedCallback->getRetryCount());
        $this->assertEquals($logResponse, $retrievedCallback->getLogResponse());
    }

    public function createDataProvider(): array
    {
        return [
            'logResponse: false' => [
                'url' => 'http://example.com/1/',
                'requestHash' => 'request_hash_1',
                'logResponse' => false,
            ],
            'logResponse: true' => [
                'url' => 'http://example.com/2/',
                'requestHash' => 'request_hash_2',
                'logResponse' => true,
            ],
        ];
    }


    public function testIncrementRetryCount()
    {
        $url = 'http://example.com';
        $requestHash = 'request_hash';

        $callback = new Callback();
        $callback->setUrl($url);
        $callback->setRequestHash($requestHash);

        $this->assertSame(0, $callback->getRetryCount());

        $callback->incrementRetryCount();
        $this->assertSame(1, $callback->getRetryCount());

        $callback->incrementRetryCount();
        $this->assertSame(2, $callback->getRetryCount());

        $callback->incrementRetryCount();
        $this->assertSame(3, $callback->getRetryCount());

        $this->entityManager->persist($callback);
        $this->entityManager->flush();

        $id = $callback->getId();

        $this->entityManager->persist($callback);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $retrievedCallback = $this->entityManager->find(Callback::class, $id);
        $this->assertSame(3, $retrievedCallback->getRetryCount());
    }

    public function testHashUrlUniqueIndex()
    {
        $url = 'http://example.com';
        $requestHash = 'request_hash';

        $callback1 = new Callback();
        $callback1->setUrl($url);
        $callback1->setRequestHash($requestHash);

        $callback2 = new Callback();
        $callback2->setUrl($url);
        $callback2->setRequestHash($requestHash);

        $this->entityManager->persist($callback1);
        $this->entityManager->persist($callback2);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->entityManager->flush();
    }
}
