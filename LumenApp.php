<?php

namespace Curia\Swoole;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Application;
use Laravel\Lumen\Http\Request as LumenRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Throwable;

class LumenApp extends Application
{
    /**
     * contextual object
     *
     * @var array
     */
    public static $contextualObject = [
        'cache',
        'cache.store',
        'request',
        'session',
        'session.store',
    ];

    /**
     * Get an object from container or context
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if ($this->inCotoutine()) {
            switch ($abstract) {
                case 'redis':
                    return $this->getRedisFromPool();
            }

            if (in_array($abstract, static::$contextualObject) && Context::has($abstract)) {
                return Context::get($abstract);
            }
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * 是否在协程环境中
     *
     * @return bool
     */
    public static function inCotoutine()
    {
        return \Co::getCid() > 0;
    }

    /**
     * Get redis from pool
     *
     * @return mixed
     */
    public function getRedisFromPool()
    {
        $pool = $this->make('redis.pool');
        $redis = $pool->get();
        defer(function () use ($pool, $redis) {
            $pool->put($redis);
        });
        return $redis;
    }

    /**
     * Dispatch the incoming request.
     *
     * @param SymfonyRequest|null $request
     * @return Response
     */
    public function dispatch($request = null)
    {
        [$method, $pathInfo] = $this->parseIncomingRequest($request);

        try {
            $this->boot();

            return $this->sendThroughPipeline($this->middleware, function ($request) use ($method, $pathInfo) {
                Server::setRequestInCoroutine($request);

                if (isset($this->router->getRoutes()[$method . $pathInfo])) {
                    return $this->handleFoundRoute([true, $this->router->getRoutes()[$method . $pathInfo]['action'], []]);
                }

                return $this->handleDispatcherResponse(
                    $this->createDispatcher()->dispatch($method, $pathInfo)
                );
            });
        } catch (Throwable $e) {
            return $this->prepareResponse($this->sendExceptionToHandler($e));
        }
    }

    /**
     * Handle a route found by the dispatcher.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function handleFoundRoute($routeInfo)
    {
        Context::set('current_route', $routeInfo);
        $this['request']->setRouteResolver(function () {
            return Context::get('current_route');
        });

        $action = $routeInfo[1];

        // Pipe through route middleware...
        if (isset($action['middleware'])) {
            $middleware = $this->gatherMiddlewareClassNames($action['middleware']);

            return $this->prepareResponse($this->sendThroughPipeline($middleware, function () {
                return $this->callActionOnArrayBasedRoute($this['request']->route());
            }));
        }

        return $this->prepareResponse(
            $this->callActionOnArrayBasedRoute($routeInfo)
        );
    }

    /**
     * Get all aliases
     *
     * @return string[]
     */
    public function aliases()
    {
        return $this->aliases;
    }
}
