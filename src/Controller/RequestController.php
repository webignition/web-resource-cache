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
use App\Services\CallbackUrlValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\HttpHeaders\Headers;

class RequestController
{
    /**
     * @var CallbackUrlValidator
     */
    private $callbackUrlValidator;

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
        CallbackUrlValidator $callbackUrlValidator,
        CachedResourceManager $cachedResourceManager,
        CachedResourceValidator $cachedResourceValidator,
        CallbackFactory $callbackFactory,
        CallbackManager $callbackManager,
        MessageBusInterface $messageBus
    ) {
        $this->callbackUrlValidator = $callbackUrlValidator;
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

        if (empty($url) || empty($callbackUrl) || !$this->callbackUrlValidator->isValid($callbackUrl)) {
            return new Response('', 400);
        }

        $parameters = $this->createParametersFromRequest($requestData->get('parameters'));
        $headers = $this->createHeadersFromRequest($requestData->get('headers'));

        $requestIdentifier = new RequestIdentifier($url, array_merge($headers->toArray(), $parameters));
        $requestHash = $requestIdentifier->getHash();

        $logCallbackResponse = $requestData->has('log-callback-response')
            ? $requestData->getBoolean('log-callback-response')
            : false;

        $callback = $this->callbackManager->findByRequestHashAndUrl($requestHash, $callbackUrl);

        if ($callback) {
            if ($callback->getLogResponse() !== $logCallbackResponse) {
                $callback->setLogResponse($logCallbackResponse);
                $this->callbackManager->persist($callback);
            }
        } else {
            $callback = $this->callbackFactory->create($requestHash, $callbackUrl, $logCallbackResponse);
            $this->callbackManager->persist($callback);
        }

        $cachedResource = $this->cachedResourceManager->find($requestHash);
        if ($cachedResource && $this->cachedResourceValidator->isFresh($cachedResource)) {
            $this->messageBus->dispatch(new SendResponse(
                (new SuccessResponse($requestHash))->jsonSerialize()
            ));
        } else {
            $this->messageBus->dispatch(new RetrieveResource($requestHash, $url, $headers, $parameters));
        }

        return new JsonResponse((string) $requestIdentifier, 200);
    }

    private function createHeadersFromRequest($requestHeaders): Headers
    {
        $headerValues = [];

        if (is_string($requestHeaders)) {
            $headerValues = json_decode($requestHeaders, true);

            if (!is_array($headerValues)) {
                $headerValues = [];
            }
        }

        return new Headers($headerValues);
    }

    private function createParametersFromRequest($requestParameters): array
    {
        $parameters = [];

        if (is_string($requestParameters)) {
            $parameters = json_decode($requestParameters, true);

            if (!is_array($parameters)) {
                $parameters = [];
            }
        }

        return $parameters;
    }
}
