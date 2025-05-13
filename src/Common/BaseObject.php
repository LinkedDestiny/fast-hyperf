<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Common;

use LinkCloud\Fast\Hyperf\Annotations\ArrayType;
use LinkCloud\Fast\Hyperf\Annotations\EnumView;
use LinkCloud\Fast\Hyperf\Helpers\StringHelper;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

class BaseObject
{
    /**
     * 是否允许空值
     * @var bool
     */
    protected bool $withNullValue = false;

    public function __construct(array $data = [])
    {
        $this->fromArray($data);
    }

    /**
     * getter
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            return $this->{$name};
        }
    }

    /**
     * setter
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (property_exists($this, $name)){
            $this->$name = $value;
        }
    }

    public static function getAllProperty(array $without = []): array
    {
        $reflectClass = new ReflectionClass(get_called_class());
        $properties = $reflectClass->getProperties(ReflectionProperty::IS_PUBLIC);
        $result = [];
        foreach ($properties as $property) {
            $name = StringHelper::uncamelize($property->getName());
            if (!in_array($name, $without)) {
                $result[] = $name;
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $reflectClass = new ReflectionClass($this);
        $properties = $reflectClass->getProperties(ReflectionProperty::IS_PUBLIC);

        $result = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $this->$name;
            if ($name == 'withNullValue' || (is_null($value) && !$this->withNullValue)) {
                continue;
            }
            $value = $this->ArrayTrait_toArrayValue($property, $value);
            $key = StringHelper::uncamelize($name);
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * @param array $values
     * @return void
     */
    public function fromArray(array $values): void
    {
        foreach ($values as $name => $value) {
            $name = StringHelper::camelize($name);
            try {
                $property = new ReflectionProperty(static::class, $name);
                $value = $this->ArrayTrait_fromArrayValue($property, $value);
            } catch (ReflectionException $e) {
            }
            $func = 'set' . ucfirst($name);
            if (method_exists($this, $func)) {
                $this->$func($value);
            } elseif (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    private function ArrayTrait_make($class, $val): mixed
    {
        // 如果 值是对象 就直接返回
        if (is_object($val)) {
            return $val;
        }
        if (is_subclass_of($class, BaseEnum::class)) {
            if ($class::hasValue($val)) {
                return $class::byValue($val);
            }
            $val = intval($val);
            if ($class::hasValue($val)) {
                return $class::byValue($val);
            }
            throw new RuntimeException($class . ' enum value not exists');
        } elseif (class_exists($class)) {
            $obj = new $class();
            if (method_exists($obj, 'fromArray')) {
                $obj->fromArray($val);
                $val = $obj;
            }
            return $val;
        }
        return $val;
    }

    private function ArrayTrait_toArrayValue(ReflectionProperty $property, mixed $value)
    {
        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return $value->toArray();
            } elseif ($value instanceof BaseEnum) {
                $attributes = $property->getAttributes(EnumView::class);
                if (empty($attributes)) {
                    return $value->getValue();
                }
                $flags = $attributes[0]->newInstance()->flags;
                $output = [];
                if ($flags & EnumView::ENUM_VALUE) {
                    $output['value'] = $value->getValue();
                }
                if ($flags & EnumView::ENUM_MESSAGE) {
                    $output['message'] = $value->getMessage();
                }
                return $output;
            } else {
                return get_object_vars($value);
            }
        } else if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->ArrayTrait_toArrayValue($property, $val);
            }
            return $value;
        }
        return $value;
    }

    private function ArrayTrait_fromArrayValue(ReflectionProperty $property, mixed $value)
    {
        $propertyType = $property->getType()->getName();
        switch ($propertyType) {
            case 'int':
            {
                $value = intval($value);
                break;
            }
            case 'string':
            {
                $value = strval($value);
                break;
            }
            case 'bool':
            {
                $value = boolval($value);
                break;
            }
            case 'array':
            {
                $attributes = $property->getAttributes(ArrayType::class);
                if (empty($attributes)) {
                    break;
                }
                $propertyClass = $attributes[0]->newInstance()->valueType;
                if (!class_exists($propertyClass)) {
                    break;
                }
                foreach ($value as &$val) {
                    $val = $this->ArrayTrait_make($propertyClass, $val);
                }
                unset($val);
                break;
            }
            default:
            {
                $value = $this->ArrayTrait_make($propertyType, $value);
                break;
            }
        }
        return $value;
    }
}