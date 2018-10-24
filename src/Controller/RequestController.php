<?php

namespace App\Controller;

use App\Entity\RetrieveRequest;
use App\Model\RequestIdentifier;
use App\Resque\Job\RetrieveResourceJob;
use App\Services\RetrieveRequestManager;
use App\Services\ResqueQueueService;
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
     * @var RetrieveRequestManager
     */
    private $retrieveRequestManager;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    public function __construct(
        Whitelist $callbackUrlWhitelist,
        RetrieveRequestManager $retrieveRequestManager,
        ResqueQueueService $resqueQueueService
    ) {
        $this->callbackUrlWhitelist = $callbackUrlWhitelist;
        $this->retrieveRequestManager = $retrieveRequestManager;
        $this->resqueQueueService = $resqueQueueService;
    }

    public function requestAction(Request $request): Response
    {
        $requestData = $request->request;
        $url = trim($requestData->get('url'));
        $callbackUrl = trim($requestData->get('callback'));

        if (empty($url) || !$this->callbackUrlWhitelist->matches($callbackUrl)) {
            return new Response('', 400);
        }

        $headers = new Headers($requestData->get('headers') ?? []);
        $requestIdentifier = new RequestIdentifier($url, $headers);
        $retrieveRequest = $this->retrieveRequestManager->find((string) $requestIdentifier);

        if (empty($retrieveRequest)) {
            $retrieveRequest = new RetrieveRequest();
            $retrieveRequest->setUrl($url);
            $retrieveRequest->setHeaders($headers);
            $retrieveRequest->setHash($requestIdentifier);
        }

        $retrieveRequest->addCallbackUrl($callbackUrl);
        $this->retrieveRequestManager->persist($retrieveRequest);

        $resqueJobArgs = ['request-hash' => $retrieveRequest->getHash()];

        if (!$this->resqueQueueService->contains(RetrieveResourceJob::QUEUE_NAME, $resqueJobArgs)) {
            $this->resqueQueueService->enqueue(new RetrieveResourceJob($resqueJobArgs));
        }

        return new JsonResponse((string) $requestIdentifier, 200);
    }
}
