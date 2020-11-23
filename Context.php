<?php

namespace Curia\Swoole;

use Swoole\Coroutine;

class Context
{
    //todo
    public static function context()
    {
        return Coroutine::getContext(Coroutine::getCid());
    }

    //todo
    public static function has($key)
    {
        $context = static::context();

        return isset($context[$key]);
    }

    //todo
    public static function get($key)
    {
        $context = static::context();

        return $context[$key];
    }

    //todo
    public static function set($key, $value)
    {
        self::context()[$key] = $value;
    }
}