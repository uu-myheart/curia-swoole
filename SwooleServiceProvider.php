<?php

namespace Curia\Swoole;

use Illuminate\Support\ServiceProvider;
use Curia\Pool\RedisPool;
use Swoole\Database\RedisConfig;

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
	public function registerCommand()
	{
		$this->commands([HttpServerCommand::class]);
	}

	//todo
    public function registerRedisPool()
    {
        $this->app->singleton('redis.pool', function ($app) {
            $config = $this->app['config']->get('database.redis.default');
            return new RedisPool($config);
        });
	}
}