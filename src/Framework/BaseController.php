<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework;

use Hyperf\Di\Annotation\Inject;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\HttpServer\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class BaseController
{
    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected ServerRequestInterface $request;

    #[Inject]
    protected Response $response;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        try {
            $this->request = $container->get(RequestInterface::class);
            $this->response = $container->get(ResponseInterface::class);
        } catch (Throwable $e) {
        }
    }

    public function attribute($key, $defaultValue = null)
    {
        return $this->request->getAttribute($key, $defaultValue);
    }

    /**
     * 真实 ip
     * @param string $headerName
     * @return mixed|string
     */
    public function getClientIp(string $headerName = 'x-real-ip'): ?string
    {
        $client = $this->request->getServerParams();
        $xri = $this->request->getHeader($headerName);
        if (!empty($xri)) {
            $clientAddress = $xri[0];
        } else {
            $clientAddress = $client['remote_addr'];
        }
        $xff = $this->request->getHeader('x-forwarded-for');
        if ($clientAddress === '127.0.0.1') {
            if (!empty($xri)) {
                // 如果有xri 则判定为前端有NGINX等代理
                $clientAddress = $xri[0];
            } elseif (!empty($xff)) {
                // 如果不存在xri 则继续判断xff
                $list = explode(',', $xff[0]);
                if (isset($list[0])) {
                    $clientAddress = $list[0];
                }
            }
        }
        return $clientAddress;
    }
}
