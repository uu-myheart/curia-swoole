<?php

namespace Curia\Swoole;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Application;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Throwable;

class LumenApp extends Application
{
    /**
     * contextual object
     *
     * @var array
     */
    protected $contextualObject = [
        'request',
        'session.store',
    ];

    // todo
    public function make($abstract, array $parameters = [])
    {
        if ($this->inCotoutine()) {
            switch ($abstract) {
                case 'redis':
                    return $this->getRedisFromPool();
            }

            if (in_array($abstract, $this->contextualObject)) {
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

    //todo
    public function getRedisFromPool()
    {
        $pool = $this->make('redis.pool');
        $redis = $pool->get();
        dump(get_class($redis));
        defer(function () use ($pool, $redis) {
            $pool->put($redis);
        });
        return $redis;
    }

    //todo
    public function resolveContextualObject($abstract, $parameters)
    {
        // unset($this->instances[$abstract]);
        // $object = parent::make($abstract, $parameters);
        // Context::set($abstract, $object);
        // return $object;
    }

    /**
     * Dispatch the incoming request.
     *
     * @param  SymfonyRequest|null  $request
     * @return Response
     */
    public function dispatch($request = null)
    {
        [$method, $pathInfo] = $this->parseIncomingRequest($request);

        try {
            $this->boot();

            return $this->sendThroughPipeline($this->middleware, function ($request) use ($method, $pathInfo) {
                Context::set('request', $request);

                if (isset($this->router->getRoutes()[$method.$pathInfo])) {
                    return $this->handleFoundRoute([true, $this->router->getRoutes()[$method.$pathInfo]['action'], []]);
                }

                return $this->handleDispatcherResponse(
                    $this->createDispatcher()->dispatch($method, $pathInfo)
                );
            });
        } catch (Throwable $e) {
            return $this->prepareResponse($this->sendExceptionToHandler($e));
        }
    }
}