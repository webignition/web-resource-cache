<?php

namespace App\Tests\Unit\Model;

use App\Model\RequestIdentifier;

class RequestIdentifierTest extends \PHPUnit\Framework\TestCase
{
    public function testHeaderSetOrderDoesNotAffectHash()
    {
        $url = 'http://example.com/';

        $identifier1 = new RequestIdentifier($url, [
            'foo' => 'bar',
            'fizz' => 'buzz',
        ]);

        $identifier2 = new RequestIdentifier($url, [
            'fizz' => 'buzz',
            'foo' => 'bar',
        ]);

        $this->assertEquals($identifier1->getHash(), $identifier2->getHash());
    }
}
