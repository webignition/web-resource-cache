<?php

namespace App\Tests\Functional\Controller;

use App\Entity\GetRequest;
use App\Services\GetRequestManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class GetRequestManagerTest extends AbstractFunctionalTestCase
{
    /**
     * @var GetRequestManager
     */
    private $getRequestManager;

    protected function setUp()
    {
        parent::setUp();

        $this->getRequestManager = self::$container->get(GetRequestManager::class);
    }

    public function testFindNotExists()
    {
        $this->assertNull($this->getRequestManager->find('http://example.com/'));
    }

    public function testFind()
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $url = 'http://example.com';

        $getRequest = new GetRequest();
        $getRequest->setUrl($url);
        $getRequest->addCallbackUrl('http://foo.example.com/callback');

        $entityManager->persist($getRequest);
        $entityManager->flush();
        $entityManager->clear();

        $foundGetRequest = $this->getRequestManager->find($url);

        $this->assertEquals($getRequest, $foundGetRequest);
    }
}
