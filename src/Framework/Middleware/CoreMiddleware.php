<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework\Middleware;

use Hyperf\Codec\Json;
use Hyperf\Context\Context;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use Hyperf\HttpMessage\Server\ResponsePlusProxy;
use Hyperf\HttpMessage\Stream\SwooleStream;
use InvalidArgumentException;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Method\MethodParametersManager;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Property\PropertyManager;
use LinkCloud\Fast\Hyperf\Common\BaseObject;
use LinkCloud\Fast\Hyperf\Constants\PropertyScope;
use LinkCloud\Fast\Hyperf\Framework\Entity\Response\CommonResponse;
use LinkCloud\Fast\Hyperf\Helpers\StringHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swow\Psr7\Message\ResponsePlusInterface;

class CoreMiddleware extends \Hyperf\HttpServer\CoreMiddleware
{
    protected function parseMethodParameters(string $controller, string $action, array $arguments): array
    {
        $definitions = $this->getMethodDefinitionCollector()->getParameters($controller, $action);
        return $this->getInjections($definitions, "{$controller}::{$action}", $arguments);
    }

    /**
     * Transfer the non-standard response content to a standard response object.
     *
     * @param null|array|Arrayable|Jsonable|string $response
     */
    protected function transferToResponse($response, ServerRequestInterface $request): ResponsePlusInterface
    {
        if (is_string($response)) {
            return $this->response()->addHeader('content-type', 'text/plain')->setBody(new SwooleStream($response));
        }

        if ($response instanceof ResponseInterface) {
            return new ResponsePlusProxy($response);
        }

        if (is_array($response) || $response instanceof Arrayable) {
            return $this->response()
                ->addHeader('content-type', 'application/json')
                ->setBody(new SwooleStream(Json::encode($response)));
        }

        if ($response instanceof Jsonable) {
            return $this->response()
                ->addHeader('content-type', 'application/json')
                ->setBody(new SwooleStream((string) $response));
        }

        //object
        if (is_object($response)) {
            $commonResponse = new CommonResponse();
            $commonResponse->data = $response;
            return $this->response()
                ->addHeader('content-type', 'application/json')
                ->setBody(new SwooleStream(Json::encode($commonResponse->toArray())));
        }

        if ($this->response()->hasHeader('content-type')) {
            return $this->response()->setBody(new SwooleStream((string) $response));
        }

        return $this->response()->addHeader('content-type', 'text/plain')->setBody(new SwooleStream((string) $response));
    }

    private function getInjections(array $definitions, string $callableName, array $arguments): array
    {
        $injections = [];
        foreach ($definitions ?? [] as $pos => $definition) {
            $value = $arguments[$pos] ?? $arguments[$definition->getMeta('name')] ?? null;
            if ($value === null) {
                if ($definition->getMeta('defaultValueAvailable')) {
                    $injections[] = $definition->getMeta('defaultValue');
                } elseif ($definition->allowsNull()) {
                    $injections[] = null;
                } elseif ($this->container->has($definition->getName())) {
                    $obj = $this->container->get($definition->getName());
                    $injections[] = $this->validateAndMap($callableName, $definition->getMeta('name'), $definition->getName(), $obj);
                } else {
                    throw new InvalidArgumentException("Parameter '{$definition->getMeta('name')}' "
                        . "of {$callableName} should not be null");
                }
            } else {
                $injections[] = $this->getNormalizer()->denormalize($value, $definition->getName());
            }
        }
        return $injections;
    }

    /**
     * @param string $callableName 'App\Controller\DemoController::index'
     * @param string $paramName
     * @param string $className
     * @param $obj
     * @return mixed
     */
    private function validateAndMap(string $callableName, string $paramName, string $className, $obj): mixed
    {
        [$controllerName, $methodName] = explode('::', $callableName);
        $methodParameter = MethodParametersManager::getMethodParameter($controllerName, $methodName, $paramName);
        if ($methodParameter == null) {
            return $obj;
        }
//        $validator = make(DataObjectValidator::class);
        /** @var ServerRequestInterface $request */
        $request = Context::get(ServerRequestInterface::class);
        $param = [];
        if ($methodParameter->isRequestBody()) {
            $param = $request->getParsedBody();
        } elseif ($methodParameter->isRequestQuery()) {
            $param = $request->getQueryParams();
        } elseif ($methodParameter->isRequestFormData()) {
            $param = $request->getParsedBody();
        }

        if (empty($param)) {
            $param = [];
        }
        $param = $this->mapObject($className, $param);

//        //validate
//        if ($methodParameter->isValid()) {
//            $validator->validate($className, $param);
//        }
        return new $className($param);
    }

    private function mapObject(string|BaseObject $className, array $param): array
    {
        /** @var ServerRequestInterface $request */
        $request = Context::get(ServerRequestInterface::class);
        $properties = PropertyManager::getProperty($className);
        foreach ($properties as $fieldName => $property) {
            $fieldName = StringHelper::uncamelize($fieldName);
            if (is_subclass_of($property->type, BaseObject::class)) {
                $param[$fieldName] = $this->mapObject($property->type, $param[$fieldName] ?? []);
            }
            switch ($property->scope->getValue()) {
                case PropertyScope::ATTRIBUTE: {
                    $param[$fieldName] = $request->getAttribute($fieldName);
                    break;
                }
                case PropertyScope::HEADER: {
                    $param[$fieldName] = $request->getHeaderLine($fieldName);
                    break;
                }
                default: {
                    break;
                }
            }
        }
        return $param;
    }
}
