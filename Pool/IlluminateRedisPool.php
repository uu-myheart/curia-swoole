<?php

namespace Curia\Swoole\Pool;

use Illuminate\Redis\RedisManager;
use Swoole\ConnectionPool;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;

class IlluminateRedisPool extends ConnectionPool
{
    /**
     * illuminate/redis pool
     *
     * IlluminateRedisPool constructor.
     * @param array $config
     * @param int $size
     */
    public function __construct(array $config, int $size = self::DEFAULT_SIZE)
    {
        $client = Arr::get($config, 'client', 'phpredis');
        $app = Container::getInstance();

        parent::__construct(function () use ($client, $config, $app) {
            return new RedisManager($app, $client, $config);
        }, $size);
    }
}
