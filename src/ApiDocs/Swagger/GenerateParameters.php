<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\ApiDocs\Swagger;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use LinkCloud\Fast\Hyperf\Annotations\Api\ApiHeader;
use LinkCloud\Fast\Hyperf\Annotations\Api\BaseParam;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Method\MethodParametersManager;
use Psr\Container\ContainerInterface;

class GenerateParameters
{
    public mixed $config;

    private MethodDefinitionCollectorInterface $methodDefinitionCollector;

    private ContainerInterface $container;

    private string $route;

    private string $method;

    private string $controller;

    private string $action;

    private SwaggerCommon $common;

    /**
     * @var ApiHeader[]
     */
    private array $apiHeaderArr;

    public function __construct(
        string $route,
        string $method,
        string $controller,
        string $action,
        array $apiHeaderArr
    ) {
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        $this->route = $route;
        $this->method = $method;
        $this->controller = $controller;
        $this->action = $action;
        $this->apiHeaderArr = $apiHeaderArr;
        $this->common = new SwaggerCommon();
    }

    public function generate(): array
    {
        $consumes = null;
        $parameters = $this->generateParam($this->apiHeaderArr);
        $definitions = $this->methodDefinitionCollector->getParameters($this->controller, $this->action);
        foreach ($definitions as $definition) {
            //query path
            $parameterClassName = $definition->getName();
            $paramName = $definition->getMeta('name');
            $simpleSwaggerType = $this->common->getSimpleType2SwaggerType($parameterClassName);
            if ($simpleSwaggerType !== null) {
                $property = [];
                $property['in'] = 'path';
                $property['name'] = $paramName;
                $property['required'] = true;
                $property['type'] = $simpleSwaggerType;
                $parameters[] = $property;
                continue;
            }

            if ($this->container->has($parameterClassName)) {
                $methodParameter = MethodParametersManager::getMethodParameter($this->controller, $this->action, $paramName);
                if ($methodParameter->isRequestBody()) {
                    $this->common->generateClass2schema($parameterClassName);
                    $property = [];
                    $property['in'] = 'body';
                    $property['name'] = $this->common->getSimpleClassName($parameterClassName);
                    $property['description'] = '';
                    $property['required'] = true;
                    $property['schema']['$ref'] = $this->common->getDefinitions($parameterClassName);
                    $parameters[] = $property;
                    $consumes = 'application/json';
                }
                if ($methodParameter->isRequestQuery()) {
                    $propertyArr = $this->common->getParameterClassProperty($parameterClassName, 'query');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                }
                if ($methodParameter->isRequestFormData()) {
                    $propertyArr = $this->common->getParameterClassProperty($parameterClassName, 'formData');
                    foreach ($propertyArr as $property) {
                        $parameters[] = $property;
                    }
                    $consumes = 'application/x-www-form-urlencoded';
                }
            }
        }
        if ($consumes != null) {
            SwaggerJson::$swagger['paths'][$this->route][$this->method]['consumes'] = [$consumes];
        }
        return array_values($parameters);
    }

    private function generateParam($paramArr): array
    {
        $parameters = [];
        /** @var BaseParam $param */
        foreach ($paramArr as $param) {
            if ($param->hidden) {
                continue;
            }
            $property = [];
            $property['in'] = $param->getIn();
            $property['name'] = $param->name;
            $property['type'] = $param->type;
            $param->example !== null && $property['example'] = $param->example;
            $param->default !== null && $property['default'] = $param->default;
            $param->required !== null && $property['required'] = $param->required;
            $param->description !== null && $property['description'] = $param->description;
            $parameters[] = $property;
        }
        return $parameters;
    }
}
