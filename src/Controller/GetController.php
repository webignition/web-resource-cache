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
    public function get(Request $request): Response
    {
        return new Response('', 400);
    }
}
