<?php

namespace App\Message;

class SendResponse
{
    /**
     * @var array
     */
    private $responseData;

    public function __construct(array $responseData)
    {
        $this->responseData = $responseData;
    }

    public function getResponseData()
    {
        return $this->responseData;
    }
}
