<?php

namespace App\Controller;

use App\Message\RetrieveResource;
use App\Message\SendResponse;
use App\Model\RequestIdentifier;
use App\Model\Response\SuccessResponse;
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
            $this->messageBus->dispatch(new SendResponse(new SuccessResponse($requestHash)));
        } else {
            $this->messageBus->dispatch(new RetrieveResource($requestHash, $url, $headers));
        }

        return new JsonResponse((string) $requestIdentifier, 200);
    }
}
