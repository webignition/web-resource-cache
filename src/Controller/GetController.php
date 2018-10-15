<?php

namespace App\Controller;

use App\Services\Whitelist;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetController
{
    /**
     * @var Whitelist
     */
    private $callbackUrlWhitelist;

    public function __construct(Whitelist $callbackUrlWhitelist)
    {
        $this->callbackUrlWhitelist = $callbackUrlWhitelist;
    }

    public function getAction(Request $request): Response
    {
        $requestData = $request->request;
        $url = trim($requestData->get('url'));
        $callbackUrl = trim($requestData->get('callback'));

        if (empty($url) || !$this->callbackUrlWhitelist->matches($callbackUrl)) {
            return new Response('', 400);
        }

        return new Response('', 200);
    }
}
