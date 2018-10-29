<?php

namespace App\Controller;

use App\Message\SendResponse;
use App\Model\RequestIdentifier;
use App\Model\Response\SuccessResponse;
use App\Model\RetrieveRequest;
use App\Services\CachedResourceManager;
use App\Services\CachedResourceValidator;
use App\Services\CallbackFactory;
use App\Services\CallbackManager;
use App\Services\Whitelist;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\HttpHeaders\Headers;

class RequestController
{
    /**
     * @var Whitelist
     */
    private $callbackUrlWhitelist;

    /**
     * @var CachedResourceManager
     */
    private $cachedResourceManager;

    /**
     * @var CachedResourceValidator
     */
    private $cachedResourceValidator;

    /**
     * @var CallbackFactory
     */
    private $callbackFactory;

    /**
     * @var CallbackManager
     */
    private $callbackManager;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(
        Whitelist $callbackUrlWhitelist,
        CachedResourceManager $cachedResourceManager,
        CachedResourceValidator $cachedResourceValidator,
        CallbackFactory $callbackFactory,
        CallbackManager $callbackManager,
        MessageBusInterface $messageBus
    ) {
        $this->callbackUrlWhitelist = $callbackUrlWhitelist;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->cachedResourceValidator = $cachedResourceValidator;
        $this->callbackFactory = $callbackFactory;
        $this->callbackManager = $callbackManager;
        $this->messageBus = $messageBus;
    }

    public function requestAction(Request $request): Response
    {
        $requestData = $request->request;
        $url = trim($requestData->get('url'));
        $callbackUrl = trim($requestData->get('callback'));

        if (empty($url) || empty($callbackUrl) || !$this->callbackUrlWhitelist->matches($callbackUrl)) {
            return new Response('', 400);
        }

        $headers = new Headers($requestData->get('headers') ?? []);
        $requestIdentifier = new RequestIdentifier($url, $headers);
        $requestHash = $requestIdentifier->getHash();

        $callback = $this->callbackManager->findByRequestHashAndUrl($requestHash, $callbackUrl);
        if (!$callback) {
            $callback = $this->callbackFactory->create($requestHash, $callbackUrl);
            $this->callbackManager->persist($callback);
        }

        $cachedResource = $this->cachedResourceManager->find($requestHash);
        if ($cachedResource && $this->cachedResourceValidator->isFresh($cachedResource)) {
            $sendResponseMessage = new SendResponse(new SuccessResponse($requestHash));

            // response-json => json_encode(new RebuildableDecoratedResponse(new SuccessResponse($requestHash)))

            // Fix in #173
            // Implement dispatching 'send response' message
            // using above success response as the data object

            $this->messageBus->dispatch($sendResponseMessage);
        } else {
            $retrieveRequest = new RetrieveRequest($requestHash, $url, $headers);

            // 'request-json' => json_encode($retrieveRequest),

            // Fix in #174
            // Implement dispatching 'retrieve resource' message
            // using the retrieve request as the data object
        }

        return new JsonResponse((string) $requestIdentifier, 200);
    }
}
