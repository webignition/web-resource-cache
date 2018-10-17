<?php

namespace App\Services;

class RetrieveRequestHashFactory
{
    public static function create(string $url, ?array $headers = [])
    {
        return md5($url . json_encode($headers));
    }
}
