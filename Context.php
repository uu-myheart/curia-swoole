<?php

namespace Curia\Swoole;

use Swoole\Coroutine;

class Context
{
    //todo
    public static function context()
    {
        return Coroutine::getContext();
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

    //todo
    public static function unset($key, $value)
    {
        unset(self::context()[$key]);
    }
}