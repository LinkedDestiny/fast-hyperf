<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\ApiDocs\Scanner;

use Exception;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionManager;
use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiAttribute;
use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiHeader;
use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiProperty;
use LinkCloud\Fast\Hyperf\Annotations\Api\Request\RequestBody;
use LinkCloud\Fast\Hyperf\Annotations\Api\Request\RequestFormData;
use LinkCloud\Fast\Hyperf\Annotations\Api\Request\RequestQuery;
use LinkCloud\Fast\Hyperf\Annotations\Api\Request\Valid;
use LinkCloud\Fast\Hyperf\Annotations\ArrayType;
use LinkCloud\Fast\Hyperf\Common\BaseEnum;
use LinkCloud\Fast\Hyperf\Constants\PropertyScope;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Method\MethodParameter;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Method\MethodParametersManager;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Property\Property;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Property\PropertyManager;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

class Scanner
{
    private static array $scanClassArray = [];

    private MethodDefinitionCollectorInterface $methodDefinitionCollector;

    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     * @throws Throwable
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
    }

    public function clearScanClassArray()
    {
        self::$scanClassArray = [];
    }

    /**
     * 扫描控制器中的方法.
     * @param $className
     * @param $methodName
     * @throws Throwable
     */
    public function scan($className, $methodName)
    {
        $this->setMethodParameters($className, $methodName);

        $definitionParamArr = $this->methodDefinitionCollector->getParameters($className, $methodName);
        $definitionReturn = $this->methodDefinitionCollector->getReturnType($className, $methodName);

        $definitionParamArr[] = $definitionReturn;
        foreach ($definitionParamArr as $definition) {
            $parameterClassName = $definition->getName();
            if ($this->container->has($parameterClassName)) {
                $this->scanClass($parameterClassName);
            }
        }
    }

    public function scanClass(string $className)
    {
        if (in_array($className, self::$scanClassArray)) {
            return;
        }
        self::$scanClassArray[] = $className;
        $reflectionClass = ReflectionManager::reflectClass($className);
        foreach ($reflectionClass->getProperties() ?? [] as $reflectionProperty) {
            $type = $reflectionProperty->getType();
            $fieldName = $reflectionProperty->getName();
            $isSimpleType = true;

            $propertyClass = $type->getName();
            if ($type->isBuiltin()) {    // 内建类型
                if ($propertyClass == 'array') { // 数组类型特殊处理
                    $attributes = $reflectionProperty->getAttributes(ArrayType::class);
                    if (!empty($attributes)) {
                        $propertyClass = $attributes[0]->newInstance()->valueType;
                        if (class_exists($propertyClass)) {
                            $isSimpleType = false;
                            $this->scanClass($propertyClass);
                        }
                    }
                }
            } else {
                if (!is_subclass_of($propertyClass, BaseEnum::class)) {
                    $this->scanClass($propertyClass);
                    $isSimpleType = false;
                }
            }
            $property = new Property();
            $property->type = $type->getName();
            $property->isSimpleType = $isSimpleType;
            $property->className = $propertyClass ? trim($propertyClass, '\\') : null;
            $property->scope = $this->getPropertyScope($reflectionProperty);
            PropertyManager::setContent($className, $fieldName, $property);
        }
    }

    /**
     * 设置方法中的参数.
     * @param $className
     * @param $methodName
     * @throws Throwable
     */
    private function setMethodParameters($className, $methodName)
    {
        // 获取方法的反射对象
        $ref = new ReflectionMethod($className . '::' . $methodName);
        // 获取方法上指定名称的全部注解
        $attributes = $ref->getParameters();
        $methodMark = 0;
        foreach ($attributes as $attribute) {
            $methodParameters = new MethodParameter();
            $paramName = $attribute->getName();
            $mark = 0;
            if ($attribute->getAttributes(RequestQuery::class)) {
                $methodParameters->setIsRequestQuery(true);
                ++$mark;
            }
            if ($attribute->getAttributes(RequestFormData::class)) {
                $methodParameters->setIsRequestFormData(true);
                ++$mark;
                ++$methodMark;
            }
            if ($attribute->getAttributes(RequestBody::class)) {
                $methodParameters->setIsRequestBody(true);
                ++$mark;
                ++$methodMark;
            }
            if ($attribute->getAttributes(Valid::class)) {
                $methodParameters->setIsValid(true);
            }
            if ($mark > 1) {
                throw new Exception("Parameter annotation [RequestQuery RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}:{$paramName}]");
            }
            MethodParametersManager::setContent($className, $methodName, $paramName, $methodParameters);
        }
        if ($methodMark > 1) {
            throw new Exception("Method annotation [RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}]");
        }
    }

    /**
     * @param ReflectionProperty $reflectionProperty
     * @return PropertyScope
     */
    protected function getPropertyScope(ReflectionProperty $reflectionProperty): PropertyScope
    {
        $annotation = $reflectionProperty->getAttributes(ApiProperty::class)[0] ?? null;
        if (empty($annotation)) {
            $annotation = $reflectionProperty->getAttributes(ApiAttribute::class)[0] ?? null;
        }
        if (empty($annotation)) {
            $annotation = $reflectionProperty->getAttributes(ApiHeader::class)[0] ?? null;
        }
        if (empty($annotation)) {
            return PropertyScope::BODY();
        }
        return $annotation->newInstance()->scope;
    }

    /**
     * @param string $type
     * @return bool
     */
    protected function isSimpleType(string $type): bool
    {
        return $type == 'string'
            || $type == 'boolean' || $type == 'bool'
            || $type == 'integer' || $type == 'int'
            || $type == 'double' || $type == 'float'
            || $type == 'array' || $type == 'object';
    }
}