<?php

namespace App\Tests\Functional\Controller;

use App\Entity\RetrieveRequest;
use App\Services\RetrieveRequestManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class RetrieveRequestManagerTest extends AbstractFunctionalTestCase
{
    /**
     * @var RetrieveRequestManager
     */
    private $retrieveRequestManager;

    protected function setUp()
    {
        parent::setUp();

        $this->retrieveRequestManager = self::$container->get(RetrieveRequestManager::class);
    }

    public function testFindNotExists()
    {
        $this->assertNull($this->retrieveRequestManager->find('http://example.com/'));
    }

    public function testFind()
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $url = 'http://example.com';

        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl($url);
        $retrieveRequest->addCallbackUrl('http://foo.example.com/callback');

        $entityManager->persist($retrieveRequest);
        $entityManager->flush();
        $entityManager->clear();

        $foundRetrieveRequest = $this->retrieveRequestManager->find($url);

        $this->assertEquals($retrieveRequest, $foundRetrieveRequest);
    }
}
