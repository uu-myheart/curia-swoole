<?php

namespace Curia\Swoole;

use Illuminate\Support\ServiceProvider;

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
}