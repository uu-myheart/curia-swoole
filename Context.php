<?php

namespace Curia\Swoole;

use Swoole\Coroutine;

class Context
{
    /**
     * Get context
     *
     * @return \Swoole\Coroutine\Context
     */
    public static function context()
    {
        return Coroutine::getContext();
    }

    /**
     * If an object exists in context
     *
     * @param $key
     * @return bool
     */
    public static function has($key)
    {
        $context = static::context();

        return isset($context[$key]);
    }

    /**
     * Get an object from context
     *
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        $context = static::context();

        return $context[$key];
    }

    /**
     * Set an object to context
     *
     * @param $key
     * @param $value
     */
    public static function set($key, $value)
    {
        self::context()[$key] = $value;
    }

    /**
     * Unset an object in context
     *
     * @param $key
     * @param $value
     */
    public static function unset($key, $value)
    {
        unset(self::context()[$key]);
    }
}