<?php
declare(strict_types=1);

use Hyperf\Context\Context;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpServer\Request;
use Hyperf\HttpServer\Response;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

if (! function_exists('isDev')) {
    /**
     * @return bool
     */
    function isDev(): bool
    {
        return in_array(config('app_env', 'dev'), ['dev', 'test']);
    }
}

if (! function_exists('di')) {
    /**
     * @return ContainerInterface
     */
    function di(): ContainerInterface
    {
        return ApplicationContext::getContainer();
    }
}


if (! function_exists('request')) {
    /**
     * @return Request
     */
    function request(): Request
    {
        return Context::get(ServerRequestInterface::class);
    }

}

if (! function_exists('response')) {
    /**
     * @return Response
     */
    function response(): Response
    {
        return Context::get(ResponseInterface::class);
    }

}

if (! function_exists('format_throwable')) {
    /**
     * Format a throwable to string.
     * @param Throwable $throwable
     * @return string
     */
    function format_throwable(Throwable $throwable): string
    {
        try {
            return di()->get(FormatterInterface::class)->format($throwable);
        } catch (Throwable) {
            return $throwable->getMessage();
        }
    }
}
