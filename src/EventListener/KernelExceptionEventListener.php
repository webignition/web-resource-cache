<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class KernelExceptionEventListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(GetResponseForExceptionEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return null;
        }

        $response = new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);

        $exception = $event->getException();

        if ($exception instanceof NotFoundHttpException) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            $response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if (Response::HTTP_INTERNAL_SERVER_ERROR === $response->getStatusCode()) {
            $this->logger->error(sprintf(
                '[%s]: %s',
                get_class($exception),
                $exception->getMessage()
            ));
        }

        $event->setResponse($response);
    }
}
