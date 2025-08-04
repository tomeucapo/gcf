<?php

namespace gcf\utils;

class JsonHelper
{
    public static function encode($data): string
    {
        $json = json_encode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON encode error: ' . json_last_error_msg());
        }
        return $json;
    }

    public static function decode(string $json, bool $assoc = true)
    {
        $data = json_decode($json, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON decode error: ' . json_last_error_msg());
        }
        return $data;
    }
}