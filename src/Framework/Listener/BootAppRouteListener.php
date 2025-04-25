<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Listener;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Server\ServerInterface;
use LinkCloud\Fast\Hyperf\ApiDocs\Swagger\SwaggerRoute;
use Throwable;

class BootAppRouteListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BootApplication::class,
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
        $config = $container->get(ConfigInterface::class);
        if (! $config->get('api_docs.enable', false)) {
            $logger->debug('api_docs not enable');
            return;
        }
        $outputDir = $config->get('api_docs.output_dir');
        if (! $outputDir) {
            $logger->error('/config/autoload/api_docs.php need set output_dir');
            return;
        }
        $prefix = $config->get('api_docs.prefix_url', '/swagger');
        $servers = $config->get('server.servers');
        $httpServerRouter = null;
        $httpServer = null;
        foreach ($servers as $server) {
            $router = $container->get(DispatcherFactory::class)->getRouter($server['name']);
            if (empty($httpServerRouter) && $server['type'] == ServerInterface::SERVER_HTTP) {
                $httpServerRouter = $router;
                $httpServer = $server;
            }
        }
        if (empty($httpServerRouter)) {
            $logger->warning('Swagger: http Service not started');
            return;
        }
        $swaggerRoute = new SwaggerRoute($prefix, $httpServer['name']);
        $swaggerRoute->add($httpServerRouter);
        $logger->info('Swagger Url at ' . $httpServer['host'] . ':' . $httpServer['port'] . $prefix);
    }
}
