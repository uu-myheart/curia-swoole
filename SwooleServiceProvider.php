<?php

namespace Curia\Swoole;

use Curia\Swoole\Pool\IlluminateRedisPool;
use Illuminate\Support\ServiceProvider;
use Curia\Swoole\Pool\RedisPool;

class SwooleServiceProvider extends ServiceProvider
{
    /**
     * Register swoole service.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Server::class);

        $this->registerCommand();

        $this->registerRedisPool();
    }

    /**
     * Register swoole command.
     *
     * @return void
     */
    protected function registerCommand()
    {
        $this->commands([HttpServerCommand::class]);
    }

    /**
     * Register redis pool to container
     *
     * @return void
     */
    protected function registerRedisPool()
    {
        $this->app->singleton('redis.pool', function ($app) {
            $config = $app->make('config')->get('database.redis');
            return new IlluminateRedisPool($config);
        });
    }
}