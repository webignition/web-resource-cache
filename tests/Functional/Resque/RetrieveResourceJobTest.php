<?php

namespace App\Tests\Functional\Resque;

use App\Resque\Job\RetrieveResourceJob;
use App\Tests\Functional\AbstractFunctionalTestCase;
use ResqueBundle\Resque\ContainerAwareJob;

class RetrieveResourceJobTest extends AbstractFunctionalTestCase
{
    public function testCreate()
    {
        $requestHash = 'example-hash';
        $retrieveResourceJob = new RetrieveResourceJob([
            'request-hash' => $requestHash,
        ]);

        $this->assertEquals(RetrieveResourceJob::QUEUE_NAME, $retrieveResourceJob->queue);
        $this->assertEquals(['request-hash' => $requestHash], $retrieveResourceJob->args);
    }

    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $requestHash = 'example-hash';
        $retrieveResourceJob = new RetrieveResourceJob([
            'request-hash' => $requestHash,
        ]);

        $reflector = new \ReflectionClass(ContainerAwareJob::class);
        $property = $reflector->getProperty('kernel');
        $property->setAccessible(true);
        $property->setValue($retrieveResourceJob, 'foo');

//        $reflection = new \ReflectionClass($retrieveResourceJob);
//
//        $property = $reflection->getProperty('kernel');
//        $property->setAccessible(true);
//        $property->setValue('foo');

        $this->assertTrue($retrieveResourceJob->run([]));
    }
}
