<?php

namespace Curia\Swoole;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request
{
    /**
     * Whether requesting a static file.
     *
     * @param SwooleRequest $request
     * @param $publicPath
     *
     * @return bool|string File path or false
     */
    public static function staticFile(SwooleRequest $request, $publicPath)
    {
        $file = $publicPath . $request->server['request_uri'];

        return is_file($file) ? $file : false;
    }

    /**
     * Send static file response.
     *
     * @param SwooleResponse $response
     * @param $file
     *
     * @return void
     */
    public static function handleStaticFile(SwooleResponse $response, $file)
    {
        $response->status(200);

        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if ($contentType = Response::$mimeTypes[$extension]) {
            $response->header('Content-Type', $contentType);
        }

        $response->sendfile($file);
    }

    /**
     * Swoole request to illuminate request.
     *
     * @param \Swoole\Http\Request $request
     *
     * @return \Illuminate\Http\Request IlluminateRequest
     */
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