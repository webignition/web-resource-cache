<?php

namespace App\Exception;

use Symfony\Component\Messenger\Transport\AmqpExt\Exception\RejectMessageExceptionInterface;

class InvalidResponseDataException extends \Exception implements RejectMessageExceptionInterface
{
}
