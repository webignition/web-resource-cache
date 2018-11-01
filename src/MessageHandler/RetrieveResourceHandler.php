<?php

namespace App\MessageHandler;

use App\Exception\HttpTransportException;
use App\Message\RetrieveResource;
use App\Message\SendResponse;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\ResourceRetriever;
use App\Services\RetryDecider;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class RetrieveResourceHandler implements MessageHandlerInterface
{
    const MAX_RETRIES = 3;

    /**
     * @var ResourceRetriever
     */
    private $resourceRetriever;

    /**
     * @var RetryDecider
     */
    private $retryDecider;

    /**
     * @var CachedResourceManager
     */
    private $cachedResourceManager;

    /**
     * @var CachedResourceFactory
     */
    private $cachedResourceFactory;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(
        ResourceRetriever $resourceRetriever,
        RetryDecider $retryDecider,
        CachedResourceManager $cachedResourceManager,
        CachedResourceFactory $cachedResourceFactory,
        MessageBusInterface $messageBus
    ) {
        $this->resourceRetriever = $resourceRetriever;
        $this->retryDecider = $retryDecider;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->cachedResourceFactory = $cachedResourceFactory;
        $this->messageBus = $messageBus;
    }

    public function __invoke(RetrieveResource $retrieveResourceMessage)
    {
        $requestResponse = null;
        $response = null;
        $httpResponse = null;
        $responseType = null;
        $statusCode = null;
        $hasUnknownFailure = true;

        try {
            $requestResponse = $this->resourceRetriever->retrieve(
                $retrieveResourceMessage->getUrl(),
                $retrieveResourceMessage->getHeaders()
            );

            $httpResponse = $requestResponse->getResponse();
            $responseType = RetryDecider::TYPE_HTTP;
            $statusCode = $httpResponse->getStatusCode();

            $hasUnknownFailure = false;
        } catch (HttpTransportException $httpTransportException) {
            if ($httpTransportException->isCurlException()) {
                $responseType = RetryDecider::TYPE_CONNECTION;
                $statusCode = $httpTransportException->getTransportErrorCode();
                $hasUnknownFailure = false;
            } elseif ($httpTransportException->isTooManyRedirectsException()) {
                $responseType = RetryDecider::TYPE_HTTP;
                $statusCode = 301;
                $hasUnknownFailure = false;

                $httpResponse = new Response(301);
            }
        }

        $requestHash = $retrieveResourceMessage->getRequestHash();

        if ($hasUnknownFailure) {
            $this->messageBus->dispatch(
                new SendResponse((new UnknownFailureResponse($requestHash))->jsonSerialize())
            );

            return;
        }

        $hasRetryableResponse = $this->retryDecider->isRetryable($responseType, $statusCode);
        if ($hasRetryableResponse && $retrieveResourceMessage->getRetryCount() <= self::MAX_RETRIES) {
            $retrieveResourceMessage->incrementRetryCount();

            $this->messageBus->dispatch($retrieveResourceMessage);

            return;
        }

        if (200 === $statusCode) {
            $cachedResource = $this->cachedResourceManager->find($requestHash);
            if ($cachedResource) {
                $this->cachedResourceFactory->updateResponse($cachedResource, $httpResponse);
            } else {
                $cachedResource = $this->cachedResourceFactory->create(
                    $retrieveResourceMessage->getRequestHash(),
                    $retrieveResourceMessage->getUrl(),
                    $httpResponse
                );
            }

            $this->cachedResourceManager->update($cachedResource);

            $response = new SuccessResponse($requestHash);
        } else {
            $response = new KnownFailureResponse($requestHash, $responseType, $statusCode);
        }

        $this->messageBus->dispatch(new SendResponse($response->jsonSerialize()));

        return;
    }
}
