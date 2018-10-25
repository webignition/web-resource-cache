<?php

namespace App\Model\Response;

interface ResponseInterface extends \JsonSerializable
{
    public function getRequestId(): string;
}
