<?php

namespace Pada\RequestBodyBundle;

abstract class Util
{
    private const CACHE_PREFIX = 'pada_request_body_';

    public static function getCacheKey(string $class, string $method): string
    {
        return \md5(static::CACHE_PREFIX . $class . '_' . $method);
    }
}
