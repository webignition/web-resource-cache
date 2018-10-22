<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\RequestIdentifier;

abstract class AbstractResponseTest extends \PHPUnit\Framework\TestCase
{
    protected function createRequestIdentifier(string $hash): RequestIdentifier
    {
        $requestIdentifier = \Mockery::mock(RequestIdentifier::class);

        $requestIdentifier
            ->shouldReceive('__toString')
            ->andReturn($hash);

        return $requestIdentifier;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
