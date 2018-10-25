<?php

namespace App\Model\Response;

interface ResponseInterface extends \JsonSerializable
{
    public static function fromJson(string $json): ?ResponseInterface;
}
