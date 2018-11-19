<?php

namespace App\Tests\Functional\Services;

use App\Services\CallbackResponseLogger;
use App\Tests\Functional\AbstractFunctionalTestCase;
use phpmock\mockery\PHPMockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use webignition\InternetMediaType\Parameter\Parser\AttributeParserException;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;
use webignition\InternetMediaType\Parser\SubtypeParserException;
use webignition\InternetMediaType\Parser\TypeParserException;

class CallbackResponseLoggerTest extends AbstractFunctionalTestCase
{
    const LOG_PATH = '/tmp';

    public function testGetService()
    {
        $this->assertInstanceOf(CallbackResponseLogger::class, self::$container->get(CallbackResponseLogger::class));
    }

    /**
     * @dataProvider logContentTypeParserExceptionDataProvider
     *
     * @param AttributeParserException|SubtypeParserException|TypeParserException $contentTypeParserException
     */
    public function testLogContentTypeParserError($contentTypeParserException)
    {
        $requestId = 'request_hash';

        $contentType = 'unparseable content type';
        $content = 'response body';
        $response = $this->createHttpResponse($contentType, $content);

        $contentTypeParser = \Mockery::mock(ContentTypeParser::class);
        $contentTypeParser
            ->shouldReceive('parse')
            ->with($contentType)
            ->andThrow($contentTypeParserException);

        PHPMockery::mock(
            'App\Services',
            'file_put_contents'
        )->with('/tmp/request_hash.txt', $content)
            ->andReturn(true);

        $callbackResponseLogger = new CallbackResponseLogger(self::LOG_PATH, $contentTypeParser);

        $callbackResponseLogger->log($requestId, $response);

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function logContentTypeParserExceptionDataProvider(): array
    {
        return [
            'AttributeParserException' => [
                'contentTypeParserException' => new AttributeParserException('message', 1, 2),
            ],
            'SubtypeParserException' => [
                'contentTypeParserException' => new SubtypeParserException('message', 1, 2),
            ],
            'TypeParserException' => [
                'contentTypeParserException' => new TypeParserException('message', 1),
            ],
        ];
    }

    /**
     * @dataProvider logDataProvider
     *
     * @param string $requestId
     * @param ResponseInterface $response
     * @param string $expectedLogFile
     * @param string $expectedLogContent
     */
    public function testLogSuccess(
        string $requestId,
        ResponseInterface $response,
        string $expectedLogFile,
        string $expectedLogContent
    ) {
        PHPMockery::mock(
            'App\Services',
            'file_put_contents'
        )->with($expectedLogFile, $expectedLogContent)
            ->andReturn(true);

        $callbackResponseLogger = new CallbackResponseLogger(self::LOG_PATH, new ContentTypeParser());
        $callbackResponseLogger->log($requestId, $response);

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function logDataProvider(): array
    {
        return [
            'text/plain' => [
                'requestId' => 'request_hash_1',
                'response' => $this->createHttpResponse('text/plain', 'text plain content'),
                'expectedLogFile' => self::LOG_PATH . '/request_hash_1.txt',
                'expectedLogContent' => 'text plain content',
            ],
            'text/html' => [
                'requestId' => 'request_hash_2',
                'response' => $this->createHttpResponse('text/html', '<html></html>'),
                'expectedLogFile' => self::LOG_PATH . '/request_hash_2.html',
                'expectedLogContent' => '<html></html>',
            ],
            'application/json; generic' => [
                'requestId' => 'request_hash_3',
                'response' => $this->createHttpResponse('application/json', json_encode('foo')),
                'expectedLogFile' => self::LOG_PATH . '/request_hash_3.json',
                'expectedLogContent' => json_encode('foo'),
            ],
            'application/json; generic with data' => [
                'requestId' => 'request_hash_4',
                'response' => $this->createHttpResponse('application/json', json_encode([
                    'data' => [
                        'foo' => 'bar',
                    ]
                ])),
                'expectedLogFile' => self::LOG_PATH . '/request_hash_4.json',
                'expectedLogContent' => json_encode([
                    'data' => [
                        'foo' => 'bar',
                    ]
                ]),
            ],
            'application/json; httpbin response' => [
                'requestId' => 'request_hash_5',
                'response' => $this->createHttpResponse('application/json', json_encode([
                    'url' => 'http://httpbin/post',
                    'data' => json_encode([
                        'foo' => 'bar',
                    ]),
                ])),
                'expectedLogFile' => self::LOG_PATH . '/request_hash_5.json',
                'expectedLogContent' => json_encode([
                    'foo' => 'bar',
                ]),
            ],
        ];
    }

    private function createHttpResponse(string $contentType, string $content)
    {
        $body = \Mockery::mock(StreamInterface::class);
        $body
            ->shouldReceive('getContents')
            ->andReturn($content);

        $response = \Mockery::mock(ResponseInterface::class);

        $response
            ->shouldReceive('getHeaderLine')
            ->with('content-type')
            ->andReturn($contentType);

        $response
            ->shouldReceive('getBody')
            ->andReturn($body);

        return $response;
    }
}
