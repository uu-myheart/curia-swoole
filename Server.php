<?php

namespace Curia\Swoole;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Container\Container;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Server
{
	/**
     * Laravel app instance(global)
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $globalApp;

    /**
     * app(Independent in everny worker process)
     * 
     * @var \Illuminate\Contracts\Container\Container
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
     * Laravel or Lumen
     *
     * @var string
     */
	protected $appType;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(Container $app)
	{
        $this->globalApp = $app;

        $this->initialize();

		$this->loadConfig()
			->setSwooleServer()
			->registerSwooleEvents();
	}

	//todo
    protected function initialize()
    {
        \Co::set(['hook_flags'=> SWOOLE_HOOK_ALL]);
	}

	/**
	 * Load configurations
	 * 
	 * @return $this
	 */
	protected function loadConfig()
	{
		$this->config = $this->globalApp->make('config');

		return $this;
	}

	/**
	 * Set swoole http server
	 * 
	 * @return $this
	 */
	protected function setSwooleServer()
	{
	    $this->appType = $this->config->get('swoole.app') ?: 'laravel';

		$address = $this->config->get('swoole.address') ?: '0.0.0.0';
		$port = $this->config->get('swoole.port') ?: '9501';
		$swooleMode = $this->config->get('swoole.process_mode') ?: SWOOLE_BASE;
		$logFile = $this->config->get('swoole.log_file') ?: 'storage/logs/swoole.log';
		$workerNum = $this->config->get('swoole.worker_num') ?: swoole_cpu_num()*2;

		$this->server = new \swoole_http_server($address, $port, $swooleMode);

         $this->server->set([
             'log_file' => $this->globalApp->basePath($logFile),
             'worker_num' => $workerNum,
         ]);

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

    /**
     * swoole worker start event
     *
     * @param $server
     * @param $workerId
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
	public function onWorkerStart($server, $workerId)
	{
		 $this->app = require $this->globalApp->basePath('bootstrap/app.php');

		 if ($this->appType == 'laravel') {
             $this->kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
         }
	}

	/**
	 * On request events callback function
	 * 
	 * @return void
	 */
	public function onRequest(SwooleRequest $request, SwooleResponse $response)
	{
        // Handle static files.
        if ($file = Request::staticFile($request, $this->app->basePath('public'))) {
            return Request::handleStaticFile($response, $file);
        }

        $illuminateRequest = Request::toIlluminateRequest($request);
        Context::set('request', $illuminateRequest);

        // Handle the request
	    if ($this->appType == 'laravel') {
            $illuminateResponse = $this->kernel->handle($illuminateRequest);
        } else {
            $illuminateResponse = $this->app->dispatch($illuminateRequest);
        }

        // Send response
        Response::send($response, $illuminateResponse);
	}

    public function onClose()
    {

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
