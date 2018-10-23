<?php

namespace App\Tests\Unit\Entity;

use App\Entity\CachedResource;

class CachedResourceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getStoredAgeDataProvider
     *
     * @param \DateTime $lastStored
     * @param \DateTime $now
     * @param int $expectedAge
     */
    public function testGetStoredAge(\DateTime $lastStored, \DateTime $now, int $expectedAge)
    {
        $resource = new CachedResource();
        $resource->setLastStored($lastStored);

        $resource->getStoredAge($now);

        $this->assertEquals($expectedAge, $resource->getStoredAge($now));
    }

    public function getStoredAgeDataProvider()
    {
        return [
            'age: 0' => [
                'lastStored' => new \DateTime(),
                'now' => new \DateTime(),
                'expectedAge' => 0,
            ],
            'age: 5' => [
                'lastStored' => new \DateTime('2018-10-18 11:00:00'),
                'now' => new \DateTime('2018-10-18 11:00:05'),
                'expectedAge' => 5,
            ],
            'age: 60' => [
                'lastStored' => new \DateTime('2018-10-18 11:00:00'),
                'now' => new \DateTime('2018-10-18 11:01:00'),
                'expectedAge' => 60,
            ],
            'age: 3600' => [
                'lastStored' => new \DateTime('2018-10-18 11:00:00'),
                'now' => new \DateTime('2018-10-18 12:00:00'),
                'expectedAge' => 3600,
            ],
            'age: 86400' => [
                'lastStored' => new \DateTime('2018-10-18 11:00:00'),
                'now' => new \DateTime('2018-10-19 11:00:00'),
                'expectedAge' => 86400,
            ],
        ];
    }

    public function testGetStoredAgeForNow()
    {
        $resource = new CachedResource();
        $this->assertEquals(0, $resource->getStoredAge());
    }
}
