<?php

namespace App\Controller;

use App\Entity\GetRequest;
use App\Services\GetRequestManager;
use App\Services\Whitelist;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetController
{
    /**
     * @var Whitelist
     */
    private $callbackUrlWhitelist;

    /**
     * @var GetRequestManager
     */
    private $getRequestManager;

    public function __construct(Whitelist $callbackUrlWhitelist, GetRequestManager $getRequestManager)
    {
        $this->callbackUrlWhitelist = $callbackUrlWhitelist;
        $this->getRequestManager = $getRequestManager;
    }

    public function getAction(Request $request): Response
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

        return new Response('', 200);
    }
}
