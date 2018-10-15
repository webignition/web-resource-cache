<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetController
{
    /**
     * @Route("/get", name="get", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function get(Request $request): Response
    {
        return new Response('', 400);
    }
}
