<?php

namespace App\Tests\Functional\Entity;

use App\Entity\Callback;
use App\Tests\Functional\AbstractFunctionalTestCase;
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

    public function testCreate()
    {
        $url = 'http://example.com';
        $requestHash = 'request_hash';

        $callback = new Callback();
        $callback->setUrl($url);
        $callback->setRequestHash($requestHash);

        $this->assertNull($callback->getId());
        $this->assertEquals($url, $callback->getUrl());
        $this->assertEquals($requestHash, $callback->getRequestHash());
        $this->assertEquals(0, $callback->getRetryCount());
        $this->entityManager->persist($callback);
        $this->entityManager->flush();

        $this->assertNotNull($callback->getId());

        $id = $callback->getId();

        $this->entityManager->clear();

        $retrievedCallback = $this->entityManager->find(Callback::class, $id);

        $this->assertEquals($url, $retrievedCallback->getUrl());
        $this->assertEquals($requestHash, $retrievedCallback->getRequestHash());
        $this->assertEquals(0, $retrievedCallback->getRetryCount());
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
}
