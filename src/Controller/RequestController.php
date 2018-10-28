<?php

namespace App\Controller;

use App\Model\RequestIdentifier;
use App\Model\Response\RebuildableDecoratedResponse;
use App\Model\Response\SuccessResponse;
use App\Model\RetrieveRequest;
use App\Resque\Job\RetrieveResourceJob;
use App\Resque\Job\SendResponseJob;
use App\Services\CachedResourceManager;
use App\Services\CachedResourceValidator;
use App\Services\CallbackFactory;
use App\Services\CallbackManager;
use App\Services\ResqueQueueService;
use App\Services\RetrieveResourceJobManager;
use App\Services\Whitelist;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use webignition\HttpHeaders\Headers;

class RequestController
{
    /**
     * @var Whitelist
     */
    private $callbackUrlWhitelist;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

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
     * @var RetrieveResourceJobManager
     */
    private $retrieveResourceJobManager;

    public function __construct(
        Whitelist $callbackUrlWhitelist,
        ResqueQueueService $resqueQueueService,
        CachedResourceManager $cachedResourceManager,
        CachedResourceValidator $cachedResourceValidator,
        CallbackFactory $callbackFactory,
        CallbackManager $callbackManager,
        RetrieveResourceJobManager $retrieveResourceJobManager
    ) {
        $this->callbackUrlWhitelist = $callbackUrlWhitelist;
        $this->resqueQueueService = $resqueQueueService;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->cachedResourceValidator = $cachedResourceValidator;
        $this->callbackFactory = $callbackFactory;
        $this->callbackManager = $callbackManager;
        $this->retrieveResourceJobManager = $retrieveResourceJobManager;
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
            $sendResponseJob = new SendResponseJob([
                'response-json' => json_encode(new RebuildableDecoratedResponse(new SuccessResponse($requestHash))),
            ]);

            if (!$this->resqueQueueService->contains($sendResponseJob)) {
                $this->resqueQueueService->enqueue($sendResponseJob);
            }
        } else {
            $retrieveRequest = new RetrieveRequest($requestHash, $url, $headers);

            $retrieveResourceJob = new RetrieveResourceJob([
                'request-json' => json_encode($retrieveRequest),
            ]);

            if (!$this->retrieveResourceJobManager->contains($retrieveResourceJob)) {
                $this->retrieveResourceJobManager->enqueue($retrieveResourceJob);
            }
        }

        return new JsonResponse((string) $requestIdentifier, 200);
    }
}
