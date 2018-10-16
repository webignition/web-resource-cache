<?php

namespace App\Controller;

use App\Entity\GetRequest;
use App\Resque\Job\GetResourceJob;
use App\Services\GetRequestManager;
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
     * @var GetRequestManager
     */
    private $getRequestManager;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    public function __construct(
        Whitelist $callbackUrlWhitelist,
        GetRequestManager $getRequestManager,
        ResqueQueueService $resqueQueueService
    ) {
        $this->callbackUrlWhitelist = $callbackUrlWhitelist;
        $this->getRequestManager = $getRequestManager;
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

        $getRequest = $this->getRequestManager->find($url);
        if (empty($getRequest)) {
            $getRequest = new GetRequest();
            $getRequest->setUrl($url);
        }

        $getRequest->addCallbackUrl($callbackUrl);
        $this->getRequestManager->persist($getRequest);

        $resqueJobArgs = ['id' => $getRequest->getId()];

        if (!$this->resqueQueueService->contains(GetResourceJob::QUEUE_NAME, $resqueJobArgs)) {
            $this->resqueQueueService->enqueue(new GetResourceJob($resqueJobArgs));
        }

        return new Response('', 200);
    }
}
