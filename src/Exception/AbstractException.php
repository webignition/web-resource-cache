<?php

namespace App\Exception;

use Psr\Http\Message\RequestInterface;

abstract class AbstractException extends \Exception
{
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        RequestInterface $request,
        string $message = null,
        int $code = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->request = $request;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }
}
