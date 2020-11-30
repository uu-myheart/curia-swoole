<?php

namespace Curia\Swoole;

use Swoole\Database\RedisPool;
use Swoole\Database\RedisConfig;
use Illuminate\Contracts\Container\Container;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Illuminate\Http\Request as IlluminateRequest;

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
        $this->loadConfig();
        $this->initialize();
        $this->setSwooleServer()->registerSwooleEvents();
    }

    /**
     * Set app tpye
     *
     * @param string $type
     */
    public function setAppType(string $type)
    {
        $this->appType = $type;

        return $this;
    }

    /**
     * initizlize
     *
     * @return void
     */
    protected function initialize()
    {
        \Co::set(['hook_flags' => SWOOLE_HOOK_ALL]);
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
        $workerNum = $this->config->get('swoole.worker_num') ?: swoole_cpu_num() * 2;

        $this->server = new \Swoole\Http\Server($address, $port, $swooleMode);

        $this->server->set([
            'log_file' => $this->globalApp->basePath($logFile),
            'worker_num' => $workerNum,
        ]);

        return $this;
    }

    /**
     * Register swoole events
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
     * Is laravel
     *
     * @return bool
     */
    public static function isLaravel()
    {
        return app() instanceof \Illuminate\Foundation\Application;
    }

    /**
     * Is lumen
     *
     * @return bool
     */
    public static function isLumen()
    {
        return ! static::isLaravel();
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
        // drop stable instance on a request
        $this->reset();

        // Handle request
        if (static::isLaravel()) {
            $illuminateRequest = Request::toIlluminateRequest($request);
            static::setRequestInCoroutine($illuminateRequest);
            $illuminateResponse = $this->kernel->handle($illuminateRequest);
        } else {
            $illuminateRequest = Request::toIlluminateRequest($request, $isLumen = true);
            static::setRequestInCoroutine($illuminateRequest);
            $illuminateResponse = $this->app->dispatch($illuminateRequest);
        }

        // Send response
        Response::send($response, $illuminateResponse);
    }

    /**
     * Set request instance in current coroutine.
     *
     * @param IlluminateRequest $request
     */
    public static function setRequestInCoroutine(\Illuminate\Http\Request $request)
    {
        Context::set('request', $request);
        Context::set('Illuminate\Http\Request', $request);
    }

    /**
     * Drop stale instance, resolve a new one, and save it to context
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function reset()
    {
        foreach (LumenApp::$contextualObject as $item) {
            $this->app->forgetInstance($item);
        }

        foreach (LumenApp::$contextualObject as $item) {
            //todo
            Context::set($item, $this->app->make($item));
        }
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
