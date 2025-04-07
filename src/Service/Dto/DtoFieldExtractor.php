<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Service\Dto;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class DtoFieldExtractor implements DtoFieldExtractorInterface
{
    public function getFields(string $dtoClass): array
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
}
