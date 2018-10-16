<?php

namespace App\Controller;

use App\Entity\RetrieveRequest;
use App\Resque\Job\RetrieveResourceJob;
use App\Services\RetrieveRequestManager;
use App\Services\ResqueQueueService;
use App\Services\Whitelist;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $retrieveRequest = $this->retrieveRequestManager->find($url);
        if (empty($retrieveRequest)) {
            $retrieveRequest = new RetrieveRequest();
            $retrieveRequest->setUrl($url);
        }

        $retrieveRequest->addCallbackUrl($callbackUrl);
        $this->retrieveRequestManager->persist($retrieveRequest);

        $resqueJobArgs = ['id' => $retrieveRequest->getId()];

        if (!$this->resqueQueueService->contains(RetrieveResourceJob::QUEUE_NAME, $resqueJobArgs)) {
            $this->resqueQueueService->enqueue(new RetrieveResourceJob($resqueJobArgs));
        }

        return new Response('', 200);
    }
}
