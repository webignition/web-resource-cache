<?php

namespace App\MessageHandler;

use App\Exception\InvalidResponseDataException;
use App\Message\SendResponse;
use App\Model\Response\DecoratedSuccessResponse;
use App\Model\Response\SuccessResponse;
use App\Services\CachedResourceManager;
use App\Services\CallbackManager;
use App\Services\ResponseFactory;
use App\Services\ResponseSender;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendResponseHandler implements MessageHandlerInterface
{
    /**
     * @var CachedResourceManager
     */
    private $cachedResourceManager;

    /**
     * @var CallbackManager
     */
    private $callbackManager;

    /**
     * @var ResponseSender
     */
    private $responseSender;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        CachedResourceManager $cachedResourceManager,
        CallbackManager $callbackManager,
        ResponseSender $responseSender,
        ResponseFactory $responseFactory,
        LoggerInterface $logger
    ) {
        $this->cachedResourceManager = $cachedResourceManager;
        $this->callbackManager = $callbackManager;
        $this->responseSender = $responseSender;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param SendResponse $sendResponseMessage
     *
     * @throws InvalidResponseDataException
     */
    public function __invoke(SendResponse $sendResponseMessage)
    {
        $responseData = $sendResponseMessage->getResponseData();
        $response = $this->responseFactory->createFromArray($responseData);

        if (empty($response)) {
            throw new InvalidResponseDataException();
        }

        $requestHash = $response->getRequestId();

        if ($response instanceof SuccessResponse) {
            $cachedResource = $this->cachedResourceManager->find($requestHash);

            if (empty($cachedResource)) {
                return;
            }

            $response = new DecoratedSuccessResponse($response, $cachedResource);
        }

        $callbacks = $this->callbackManager->findByRequestHash($requestHash);
        foreach ($callbacks as $callback) {
            $callbackSent = $this->responseSender->send($callback, $response);

            if ($callbackSent) {
                $this->callbackManager->remove($callback);
            }
        }
    }
}
