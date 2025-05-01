<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Utils;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class DtoFieldExtractor
{
    public static function getFields(string $dtoClass): array
    {
        $fields = [];

        $reflection = new ReflectionClass($dtoClass);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $fields[] = $property->getName();
        }

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $fields[] = $param->getName();
            }
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if (str_starts_with($name, 'get')) {
                $fields[] = lcfirst(substr($name, 3));
            } elseif (str_starts_with($name, 'is')) {
                $fields[] = lcfirst(substr($name, 2));
            }
        }

        return array_unique($fields);
    }

    public static function getFieldValue(object $dto, string $field): mixed
    {
        $getter = 'get' . ucfirst($field);

        if (method_exists($dto, $getter)) {
            return $dto->$getter();
        }

        if (property_exists($dto, $field)) {
            $reflection = new ReflectionProperty($dto, $field);

            if ($reflection->isPublic()) {
                return $dto->$field;
            }

            $reflection->setAccessible(true);

            return $reflection->getValue($dto);
        }

        if (method_exists($dto, '__call')) {
            return $dto->$getter();
        }

        return null;
    }
}
