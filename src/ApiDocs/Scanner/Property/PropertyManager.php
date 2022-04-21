<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\ApiDocs\Scanner\Property;

class PropertyManager
{
    protected static array $content = [];

    protected static array $notSimpleClass = [];

    public static function setNotSimpleClass($className)
    {
        $className = trim($className, '\\');
        static::$notSimpleClass[$className] = true;
    }

    public static function setContent(string $className, string $fieldName, Property $property)
    {
        $className = trim($className, '\\');
        if (isset(static::$content[$className][$fieldName])) {
            return;
        }
        static::$content[$className][$fieldName] = $property;
    }

    public static function getProperty(string $className, ?string $fieldName = null): Property|array|null
    {
        $className = trim($className, '\\');
        if (empty($fieldName)) {
            return static::$content[$className] ?? [];
        }

        if (! isset(static::$content[$className][$fieldName])) {
            return null;
        }
        return static::$content[$className][$fieldName];
    }

    public static function getPropertyByType($className, $type, bool $isSimpleType): array
    {
        $className = trim($className, '\\');
        if (! isset(static::$content[$className])) {
            return [];
        }
        $data = [];
        foreach (static::$content[$className] as $fieldName => $propertyArr) {
            /** @var Property $property */
            foreach ($propertyArr as $property) {
                if ($property->type == $type
                    && $property->isSimpleType == $isSimpleType
                ) {
                    $data[$fieldName] = $property;
                }
            }
        }
        return $data;
    }

    /**
     * @param $className
     * @return Property[]
     */
    public static function getPropertyAndNotSimpleType($className): array
    {
        $className = trim($className, '\\');
        if (! isset(static::$notSimpleClass[$className])) {
            return [];
        }
        $data = [];
        foreach (static::$content[$className] as $fieldName => $property) {
            if ($property->isSimpleType == false) {
                $data[$fieldName] = $property;
            }
        }
        return $data;
    }
}
