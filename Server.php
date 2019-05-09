<?php

namespace Curia\Swoole;

use Illuminate\Support\Arr;
use Illuminate\Foundation\Application;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Server
{
	/**
     * Laravel app 实例(全局)
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $laravel;

    /**
     * App实例(worker进程独立)
     * 
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

	/**
	 * Swoole http server instance
	 *
	 * @var \Swoole\Http\Server
	 */
	protected $server;

	/**
	 * Swoole configurations
	 * 
	 * @var array
	 */
	protected $config;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(Application $laravel)
	{
		$this->laravel = $laravel;

		$this->loadConfig()
			->createSwooleServer()
			->registerSwooleEvents();
	}

	/**
	 * Load configurations
	 * 
	 * @return $this
	 */
	protected function loadConfig()
	{
		$this->config = app('config')->get('swoole');

		return $this;
	}

	/**
	 * Create swoole http server
	 * 
	 * @return $this
	 */
	protected function createSwooleServer()
	{
		$address = Arr::get($this->config, 'address');
		$port = Arr::get($this->config, 'port');

		$this->server = new \swoole_http_server($address, $port);

        // $this->server->set([
        //     'log_file' => '/var/log/swoole.log'
        // ]);

		return $this;
	}

	/**
	 * [registerEvents description]
	 * 
	 * @return $this;
	 */
	protected function registerSwooleEvents()
	{
		$this->server->on('workerStart', [$this, 'onWorkerStart']);
		$this->server->on('request', [$this, 'onRequest']);

		return $this;
	}

	public function onWorkerStart($server, $workerId)
	{
		// $this->app = require $this->laravel->basePath('bootstrap/app.php');

		// $this->kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
	}

	/**
	 * On request events callback function
	 * 
	 * @return [type] [description]
	 */
	public function onRequest(SwooleRequest $request, SwooleResponse $response)
	{
        $app = require $this->laravel->basePath('bootstrap/app.php');

        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

        $illuminateResponse = $kernel->handle(
            Request::toIlluminateRequest($request)
        );

        Response::send($response, $illuminateResponse);
	}

    /**
     * Start swoole http server
     * 
     * @return void
     */
	public function start()
	{
		$this->server->start();
	}
}