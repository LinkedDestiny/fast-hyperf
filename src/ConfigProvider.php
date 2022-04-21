<?php

declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf;

use Hyperf\Contract\StdoutLoggerInterface;
use LinkCloud\Fast\Hyperf\Command\CodeGen\Visitor\ModelUpdateVisitor;
use LinkCloud\Fast\Hyperf\Framework\Listener\BeforeServerListener;
use LinkCloud\Fast\Hyperf\Framework\Listener\BootAppRouteListener;
use LinkCloud\Fast\Hyperf\Framework\Log\LoggerFactory;
use LinkCloud\Fast\Hyperf\Framework\Middleware\CoreMiddleware;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                \Hyperf\HttpServer\CoreMiddleware::class                => CoreMiddleware::class,
                \Hyperf\Database\Commands\Ast\ModelUpdateVisitor::class => ModelUpdateVisitor::class,
                StdoutLoggerInterface::class                            => LoggerFactory::class,
            ],
            'listeners'    => [
                BootAppRouteListener::class,
                BeforeServerListener::class,
            ],
            'annotations'  => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
