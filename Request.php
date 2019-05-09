<?php

namespace Curia\Swoole;

use Swoole\Http\Request as SwooleRequest;
use Illuminate\Http\Request as IlluminateRequest;

class Request
{
	public static function toIlluminateRequest(SwooleRequest $request)
	{
        return IlluminateRequest::createFromBase(
            new SymfonyRequest(...static::toSymfonyParameters($request))
        );
	}

	/**
     * Transforms request parameters.
     *
     * @param \Swoole\Http\Request $request
     *
     * @return array
     */
    public static function toSymfonyParameters(SwooleRequest $request)
    {
        $get = $request->get ?? [];
        $post = $request->post ?? [];
        $cookie = $request->cookie ?? [];
        $files = $request->files ?? [];
        $header = $request->header ?? [];
        $server = $request->server ?? [];
        $server = static::transformServerParameters($server, $header);
        $content = $request->rawContent();

        return [$get, $post, [], $cookie, $files, $server, $content];
    }

    /**
     * Transforms $_SERVER array.
     *
     * @param array $server
     * @param array $header
     *
     * @return array
     */
    public static function transformServerParameters(array $server, array $header)
    {
        $__SERVER = [];

        foreach ($server as $key => $value) {
            $key = strtoupper($key);
            $__SERVER[$key] = $value;
        }

        foreach ($header as $key => $value) {
            $key = str_replace('-', '_', $key);
            $key = strtoupper($key);

            if (! in_array($key, ['REMOTE_ADDR', 'SERVER_PORT', 'HTTPS'])) {
                $key = 'HTTP_' . $key;
            }

            $__SERVER[$key] = $value;
        }

        return $__SERVER;
    }
}