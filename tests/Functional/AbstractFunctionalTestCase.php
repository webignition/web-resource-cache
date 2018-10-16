<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractFunctionalTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->client = static::createClient();
        self::$container->get('doctrine')->getConnection()->beginTransaction();
    }

    protected function tearDown()
    {
        self::$container->get('doctrine')->getConnection()->close();
        $this->client = null;
        \Mockery::close();

        parent::tearDown();
    }
}
