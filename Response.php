<?php

namespace Curia\Swoole;

class Response
{
    public static function send($response, $illuminateResponse)
    {
        static::sendHeaders($response, $illuminateResponse);
        static::sendContent($response, $illuminateResponse);
    }

    /**
     * Send HTTP headers.
     *
     * @throws \InvalidArgumentException
     */
    public static function sendHeaders($response, $illuminateResponse)
    {
        /* RFC2616 - 14.18 says all Responses need to have a Date */
        if (! $illuminateResponse->headers->has('Date')) {
            $illuminateResponse->setDate(\DateTime::createFromFormat('U', time()));
        }

        // headers
        // allPreserveCaseWithoutCookies() doesn't exist before Laravel 5.3
        $headers = $illuminateResponse->headers->allPreserveCase();
        if (isset($headers['Set-Cookie'])) {
            unset($headers['Set-Cookie']);
        }
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        // status
        $response->status($illuminateResponse->getStatusCode());

        // cookies
        // $cookie->isRaw() is supported after symfony/http-foundation 3.1
        // and Laravel 5.3, so we can add it back now
        foreach ($illuminateResponse->headers->getCookies() as $cookie) {
            $method = $cookie->isRaw() ? 'rawcookie' : 'cookie';
            $response->{$method}(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
    }

    /**
     * Send HTTP content.
     */
    public static function sendContent($response, $illuminateResponse)
    {
        if ($illuminateResponse instanceof StreamedResponse && property_exists($illuminateResponse, 'output')) {
            // TODO Add Streamed Response with output
            $response->end($illuminateResponse->output);
        } elseif ($illuminateResponse instanceof BinaryFileResponse) {
            $response->sendfile($illuminateResponse->getFile()->getPathname());
        } else {
            static::sendInChunk($response, $illuminateResponse->getContent());
        }
    }

    /**
     * Send content in chunk
     *
     * @param string $content
     */
    public static function sendInChunk($response, $content)
    {
        if (strlen($content) <= 8192) {
            $response->end($content);
            return;
        }

        foreach (str_split($content, 8192) as $chunk) {
            $response->write($chunk);
        }

        $response->end();
    }
}