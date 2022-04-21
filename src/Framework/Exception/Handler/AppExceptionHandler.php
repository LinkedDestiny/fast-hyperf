<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Exception\Handler;

use Couchbase\BaseException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpServer\Request;
use Hyperf\HttpServer\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected StdoutLoggerInterface $logger;

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var Response
     */
    protected Response $response;

    /**
     * @var Request
     */
    protected Request $request;


    public function __construct(ContainerInterface $container, StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->container = $container;
        $this->response = $container->get(Response::class);
        $this->request = $container->get(Request::class);
    }

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $msg = $throwable->getMessage();
        if ($throwable instanceof BaseException) {
            if (isDev() || $this->request->input('x-test-open')) {
                $this->logger->warning(format_throwable($throwable));
                $msg = format_throwable($throwable);
            }
            return $this->response->fail($throwable->getErrorCode(), $msg);
        }

        $this->logger->error(format_throwable($throwable));

        $message = 'Server Error！';
        if (isDev()) {
            $message = format_throwable($throwable);
        }

        return $this->response->fail('F_0000001', $message);
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
