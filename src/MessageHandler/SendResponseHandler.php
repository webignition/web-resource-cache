<?php

namespace App\MessageHandler;

use App\Message\SendResponse;
use App\Model\Response\PresentationDecoratedSuccessResponse;
use App\Model\Response\SuccessResponse;
use App\Services\CachedResourceManager;
use App\Services\CallbackManager;
use App\Services\ResponseSender;
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

    public function __construct(
        CachedResourceManager $cachedResourceManager,
        CallbackManager $callbackManager,
        ResponseSender $responseSender
    ) {
        $this->cachedResourceManager = $cachedResourceManager;
        $this->callbackManager = $callbackManager;
        $this->responseSender = $responseSender;
    }

    public function __invoke(SendResponse $sendResponseMessage)
    {
        $response = $sendResponseMessage->getResponse();
        $requestHash = $response->getRequestId();

        if ($response instanceof SuccessResponse) {
            $cachedResource = $this->cachedResourceManager->find($requestHash);

            if (empty($cachedResource)) {
                return;
            }

            $response = new PresentationDecoratedSuccessResponse($response, $cachedResource);
        }

        $callbacks = $this->callbackManager->findByRequestHash($requestHash);
        foreach ($callbacks as $callback) {
            $this->responseSender->send($callback->getUrl(), $response);
        }
    }
}
