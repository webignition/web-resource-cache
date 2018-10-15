<?php

namespace App\Controller;

use App\Services\Whitelist;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetController
{
    public function getAction(Whitelist $callbackUrlWhitelist, Request $request): Response
    {
        $requestData = $request->request;
        $url = trim($requestData->get('url'));
        $callbackUrl = trim($requestData->get('callback'));

        if (empty($url) || !$callbackUrlWhitelist->matches($callbackUrl)) {
            return new Response('', 400);
        }

        return new Response('', 200);
    }
}
