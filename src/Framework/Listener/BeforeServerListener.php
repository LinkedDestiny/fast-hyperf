<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Listener;

use Closure;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeServerStart;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Hyperf\Utils\ApplicationContext;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Scanner;
use LinkCloud\Fast\Hyperf\ApiDocs\Swagger\SwaggerJson;
use RuntimeException;
use Throwable;

class BeforeServerListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BeforeServerStart::class,
            MainCoroutineServerStart::class,
        ];
    }

    /**
     * @param object $event
     * @return void
     * @throws Throwable
     */
    public function process(object $event): void
    {
        $container = ApplicationContext::getContainer();
        $logger = $container->get(StdoutLoggerInterface::class);

        if ($event instanceof BeforeServerStart) {
            $serverName = $event->serverName;
        } else {
            /** @var MainCoroutineServerStart $event */
            $serverName = $event->name;
        }

        $scanner = make(Scanner::class);
        $router = $container->get(DispatcherFactory::class)->getRouter($serverName);

        $data = $router->getData();
        array_walk_recursive($data, function ($item) use ($scanner) {
            if ($item instanceof Handler && ! ($item->callback instanceof Closure)) {
                $prepareHandler = $this->prepareHandler($item->callback);
                if (count($prepareHandler) > 1) {
                    [$controller, $action] = $prepareHandler;
                    $scanner->scan($controller, $action);
                }
            }
        });

        $scanner->clearScanClassArray();

        if (! config('api_docs.enable', false)) {
            return;
        }
        $outputDir = config('api_docs.output_dir');
        if (! $outputDir) {
            return;
        }

        $swagger = new SwaggerJson($serverName);
        foreach ($router->getData() ?? [] as $routeData) {
            foreach ($routeData ?? [] as $methods => $handlerArr) {
                array_walk_recursive($handlerArr, function ($item) use ($swagger, $methods) {
                    if ($item instanceof Handler && ! ($item->callback instanceof Closure)) {
                        $prepareHandler = $this->prepareHandler($item->callback);
                        if (count($prepareHandler) > 1) {
                            [$controller, $methodName] = $prepareHandler;
                            $swagger->addPath($controller, $methodName, $item->route, $methods);
                        }
                    }
                });
            }
        }
        $swagger->save();
        $logger->debug('swagger server:[' . $serverName . '] file has been generated');
    }

    protected function prepareHandler($handler): array
    {
        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                return explode('@', $handler);
            }
            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        throw new RuntimeException('Handler not exist.');
    }
}