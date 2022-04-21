<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\ApiDocs\Scanner;

use Hyperf\Di\Annotation\AnnotationCollector;

class ApiAnnotationCollector
{
    /**
     * @param $className
     * @param $propertyName
     * @param $annotationClassName
     * @return null|object $annotationClassName
     */
    public static function getProperty($className, $propertyName, $annotationClassName): ?object
    {
        $propertyAnnotations = AnnotationCollector::getClassPropertyAnnotation($className, $propertyName);
        return $propertyAnnotations[$annotationClassName] ?? null;
    }

    /**
     * @param $className
     * @param $propertyName
     * @return array
     */
    public static function getClassProperty($className, $propertyName): array
    {
        return AnnotationCollector::getClassPropertyAnnotation($className, $propertyName) ?? [];
    }

    public static function classMetadata($className)
    {
        return AnnotationCollector::list()[$className]['_c'] ?? [];
    }

    public static function methodMetadata($className)
    {
        return AnnotationCollector::list()[$className]['_m'] ?? [];
    }

    public static function propertyMetadata($className)
    {
        return AnnotationCollector::list()[$className]['_p'] ?? [];
    }
}
