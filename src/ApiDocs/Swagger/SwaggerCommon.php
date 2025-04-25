<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\ApiDocs\Swagger;

use Hyperf\Database\Model\Model;
use Hyperf\Di\ReflectionManager;
use Hyperf\Context\ApplicationContext;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\ApiAnnotationCollector;
use LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Property\PropertyManager;
use LinkCloud\Fast\Hyperf\Annotations\Api\Property\ApiProperty;
use LinkCloud\Fast\Hyperf\Common\BaseEnum;
use LinkCloud\Fast\Hyperf\Constants\PropertyScope;
use LinkCloud\Fast\Hyperf\Helpers\StringHelper;
use ReflectionProperty;
use stdClass;
use Throwable;
use function Hyperf\Support\make;

class SwaggerCommon
{
    public function getDefinitions(string $className): string
    {
        return '#/definitions/' . $this->getSimpleClassName($className);
    }

    protected function getDefinition(string $className): array
    {
        return SwaggerJson::$swagger['definitions'][$this->getSimpleClassName($className)] ?? [];
    }

    public function getSimpleClassName(string $className): string
    {
        return SwaggerJson::getSimpleClassName($className);
    }

    public function getParameterClassProperty(string $parameterClassName, string $in): array
    {
        $parameters = [];
        $rc = ReflectionManager::reflectClass($parameterClassName);
        foreach ($rc->getProperties(ReflectionProperty::IS_PUBLIC) ?? [] as $reflectionProperty) {
            $property = [];
            $property['in'] = $in;
            $property['name'] = $reflectionProperty->getName();
            try {
                $property['default'] = $reflectionProperty->getValue(make($parameterClassName));
            } catch (Throwable) {
            }
            $phpType = $this->getTypeName($reflectionProperty);
            $property['type'] = $this->getType2SwaggerType($phpType);
            if (! in_array($phpType, ['integer', 'int', 'boolean', 'bool', 'string', 'double', 'float'])) {
                continue;
            }

            /** @var ApiProperty $apiModelProperty */
            $apiModelProperty = ApiAnnotationCollector::getProperty($parameterClassName, $reflectionProperty->getName(), ApiProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiProperty('');
            if ($apiModelProperty->hidden) {
                continue;
            }
            if (!empty($inAnnotation)) {
                $property['enum'] = $inAnnotation->getValue();
            }
            if ($apiModelProperty->example !== null) {
                $property['example'] = $apiModelProperty->example;
            }
            $property['description'] = $apiModelProperty->value ?? '';
            $parameters[] = $property;
        }
        return $parameters;
    }

    public function getTypeName(ReflectionProperty $rp): string
    {
        try {
            $type = $rp->getType()->getName();
        } catch (Throwable) {
            $type = 'string';
        }
        return $type;
    }

    public function getType2SwaggerType($phpType): string
    {
        return match ($phpType) {
            'int', 'integer' => 'integer',
            'boolean', 'bool' => 'boolean',
            'double', 'float' => 'number',
            'array' => 'array',
            'object' => 'object',
            default => 'string',
        };
    }

    public function getSimpleType2SwaggerType(string $phpType): ?string
    {
        return match ($phpType) {
            'int', 'integer' => 'integer',
            'boolean', 'bool' => 'boolean',
            'double', 'float' => 'number',
            'string', 'mixed' => 'string',
            default => null,
        };
    }

    public function generateClass2schema(string $className): void
    {
        if (! ApplicationContext::getContainer()->has($className)) {
            $this->generateEmptySchema($className);
            return;
        }
        $obj = ApplicationContext::getContainer()->get($className);
        if ($obj instanceof Model) {
            //$this->getModelSchema($obj);
            $this->generateEmptySchema($className);
            return;
        }

        $schema = [
            'type' => 'object',
            'properties' => [],
        ];
        $rc = ReflectionManager::reflectClass($className);
        foreach ($rc->getProperties(ReflectionProperty::IS_PUBLIC) ?? [] as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            $propertyClass = PropertyManager::getProperty($className, $fieldName);
            if ($propertyClass->scope->getValue() != PropertyScope::BODY) {
                continue;
            }
            $phpType = $propertyClass->type;
            $type = $this->getType2SwaggerType($phpType);
            /** @var ApiProperty $apiModelProperty */
            $apiModelProperty = ApiAnnotationCollector::getProperty($className, $fieldName, ApiProperty::class);
            $apiModelProperty = $apiModelProperty ?: new ApiProperty('');

            if ($apiModelProperty->hidden) {
                continue;
            }
            $property = [];
            $property['type'] = $type;
            if (! empty($inAnnotation)) {
                $property['enum'] = $inAnnotation->getValue();
            }
            $property['description'] = $apiModelProperty->name ?? '';
            if (is_subclass_of($propertyClass->className, BaseEnum::class)) {
                $property['enum'] = $propertyClass->className::getValues();
            }

            if ($apiModelProperty->example !== null) {
                $property['example'] = $apiModelProperty->example;
            }
            if ($reflectionProperty->isPublic() && $reflectionProperty->isInitialized($obj)) {
                $property['default'] = $reflectionProperty->getValue($obj);
            }
            if ($phpType == 'array') {
                if ($propertyClass->className == null) {
                    $property['items'] = (object) [];
                } else {
                    if ($propertyClass->isSimpleType) {
                        $property['items']['type'] = $this->getType2SwaggerType($propertyClass->className);
                    } else {
                        $this->generateClass2schema($propertyClass->className);
                        $property['items']['$ref'] = $this->getDefinitions($propertyClass->className);
                    }
                }
            }
            if ($type == 'object') {
                $property['items'] = (object) [];
            }
            if (! $propertyClass->isSimpleType && $phpType != 'array' && class_exists($propertyClass->className)) {
                $this->generateClass2schema($propertyClass->className);
                if (!empty($property['description'])) {
                    $definition = $this->getDefinition($propertyClass->className);
                    $definition['description'] = $property['description'];
                    SwaggerJson::$swagger['definitions'][$this->getSimpleClassName($propertyClass->className)] = $definition;
                }
                $property = ['$ref' => $this->getDefinitions($propertyClass->className)];
            }
            $schema['properties'][StringHelper::uncamelize($fieldName)] = $property;
        }

	    if (empty($schema['properties'])) {
            $schema['properties'] = new stdClass();
        }
        SwaggerJson::$swagger['definitions'][$this->getSimpleClassName($className)] = $schema;
    }

    public function isSimpleType($type): bool
    {
        return $type == 'string'
            || $type == 'boolean' || $type == 'bool'
            || $type == 'integer' || $type == 'int'
            || $type == 'double' || $type == 'float'
            || $type == 'array' || $type == 'object';
    }

    protected function generateEmptySchema(string $className)
    {
        $schema = [
            'type' => 'object',
            'properties' => new stdClass(),
        ];
        SwaggerJson::$swagger['definitions'][$this->getSimpleClassName($className)] = $schema;
    }
}
