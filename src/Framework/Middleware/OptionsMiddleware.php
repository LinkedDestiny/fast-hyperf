<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Middleware;

use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class OptionsMiddleware implements MiddlewareInterface
{

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Access-Control-Request-Headers');
        if (empty($header)) {
            $header = '*';
        }

        // 设置跨域
        $response = Context::get(ResponseInterface::class);
        $response = $response->withAddedHeader('Access-Control-Expose-Headers', '*')
            ->withAddedHeader('Access-Control-Allow-Origin', '*')
            ->withAddedHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
            ->withAddedHeader('Access-Control-Allow-Headers', $header);
        Context::set(ResponseInterface::class, $response);

        if (strtoupper($request->getMethod()) == 'OPTIONS') {
            return $response;
        }

        return $handler->handle($request);
    }
}