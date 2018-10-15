<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetController
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function getAction(Request $request): Response
    {
        $requestData = $request->request;
        $url = trim($requestData->get('url'));
        $callbackUrl = trim($requestData->get('callback'));

        if (empty($url) || empty($callbackUrl)) {
            return new Response('', 400);
        }

        return new Response('', 200);
    }
}
