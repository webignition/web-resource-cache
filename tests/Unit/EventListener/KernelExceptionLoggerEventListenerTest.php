<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\KernelExceptionEventListener;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelExceptionLoggerEventListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testInvokeForSubRequest()
    {
        $event = $this->createGetResponseForExceptionEvent(
            new Request(),
            new \Exception(),
            KernelInterface::SUB_REQUEST
        );

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldNotReceive('error');

        $eventListener = new KernelExceptionEventListener($logger);
        $returnValue = $eventListener->__invoke($event);

        $this->assertNull($returnValue);
        $this->assertFalse($event->hasResponse());
    }

    /**
     * @dataProvider invokeForNonGenericExceptionDataProvider
     *
     * @param Request $request
     * @param \Exception $exception
     * @param int $expectedResponseStatusCode
     */
    public function testInvokeForNonGenericException(
        Request $request,
        \Exception $exception,
        int $expectedResponseStatusCode
    ) {
        $event = $this->createGetResponseForExceptionEvent($request, $exception);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldNotReceive('error');

        $eventListener = new KernelExceptionEventListener($logger);
        $returnValue = $eventListener->__invoke($event);

        $this->assertNull($returnValue);
        $this->assertTrue($event->hasResponse());
        $this->assertEquals($expectedResponseStatusCode, $event->getResponse()->getStatusCode());
    }

    public function invokeForNonGenericExceptionDataProvider(): array
    {
        return [
            'NotFoundHttpException' => [
                'request' => new Request(),
                'exception' => new NotFoundHttpException('Not Found'),
                'expectedResponseStatusCode' => 404,
            ],
            'MethodNotAllowedHttpException' => [
                'request' => new Request(),
                'exception' => new MethodNotAllowedHttpException(['POST']),
                'expectedResponseStatusCode' => 405,
            ],
        ];
    }

    /**
     * @dataProvider invokeForGenericExceptionDataProvider
     *
     * @param Request $request
     * @param \Exception $exception
     * @param string $expectedLogMessage
     * @param int $expectedResponseStatusCode
     */
    public function testInvokeForGenericException(
        Request $request,
        \Exception $exception,
        string $expectedLogMessage,
        int $expectedResponseStatusCode
    ) {
        $event = $this->createGetResponseForExceptionEvent($request, $exception);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldNotReceive('error');
        $logger
            ->shouldReceive('error')
            ->once()
            ->withArgs(function (string $message) use ($expectedLogMessage) {
                $this->assertEquals($expectedLogMessage, $message);

                return true;
            });

        $eventListener = new KernelExceptionEventListener($logger);
        $returnValue = $eventListener->__invoke($event);

        $this->assertNull($returnValue);
        $this->assertTrue($event->hasResponse());
        $this->assertEquals($expectedResponseStatusCode, $event->getResponse()->getStatusCode());
    }

    public function invokeForGenericExceptionDataProvider(): array
    {
        return [
            'generic exception' => [
                'request' => new Request(),
                'exception' => new \Exception('Generic exception message'),
                'expectedLogMessage' => '[Exception]: Generic exception message',
                'expectedResponseStatusCode' => 500,
            ],
            'invalid argument exception' => [
                'request' => new Request(),
                'exception' => new \InvalidArgumentException('InvalidArgumentException exception message'),
                'expectedLogMessage' => '[InvalidArgumentException]: InvalidArgumentException exception message',
                'expectedResponseStatusCode' => 500,
            ],
        ];
    }

    private function createGetResponseForExceptionEvent(
        Request $request,
        \Exception $exception,
        ?int $requestType = KernelInterface::MASTER_REQUEST
    ): GetResponseForExceptionEvent {
        return new GetResponseForExceptionEvent(
            \Mockery::mock(KernelInterface::class),
            $request,
            $requestType,
            $exception
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
