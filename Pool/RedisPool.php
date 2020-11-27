<?php

namespace Curia\Pool;

use Illuminate\Redis\RedisManager;
use Swoole\ConnectionPool;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;

class RedisPool extends ConnectionPool
{
    public function __construct(array $config, int $size = self::DEFAULT_SIZE)
    {
        $client = Arr::get($config, 'client', 'phpredis')

        parent::__construct(function () use ($client, $config){
            $app = Container::getInstance();
            return new RedisManager($app, $client, $config);
        }, $size);
    }
}
