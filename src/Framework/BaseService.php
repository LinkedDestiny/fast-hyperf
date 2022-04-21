<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Response;
use Psr\Container\ContainerInterface;
use Throwable;

class BaseService
{
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var Response
     */
    protected Response $response;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        try {
            $this->response = $container->get(Response::class);
            $this->request = $container->get(RequestInterface::class);
        } catch (Throwable $e) {
        }
    }

    public function toArray($data, callable $handler)
    {
        if ($data instanceof BaseModel) {
            return call_user_func($handler, $data);
        }

        foreach ($data as $key => $item) {
            $data[$key] = call_user_func($handler, $item);
        }
        return $data;
    }
}