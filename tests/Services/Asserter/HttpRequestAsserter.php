<?php

namespace App\Tests\Services\Asserter;

use Psr\Http\Message\RequestInterface;

class HttpRequestAsserter extends \PHPUnit\Framework\TestCase
{
    public function assertSenderRequest(
        RequestInterface $request,
        string $expectedUrl,
        array $expectedRequestData
    ) {
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($expectedUrl, (string) $request->getUri());
        $this->assertEquals('application/json', $request->getHeaderLine('content-type'));

        $requestData = json_decode($request->getBody()->getContents(), true);
        $this->assertEquals($expectedRequestData, $requestData);

        $request->getBody()->rewind();
    }
}
