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
        if (in_array($abstract, $this->contextualObject)) {
            return Context::get($abstract);
            return Context::has($abstract)
                ? Context::get($abstract)
                : $this->resolveContextualObject($abstract, $parameters);
        }

        return parent::make($abstract, $parameters);
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