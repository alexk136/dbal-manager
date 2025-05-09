<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Service\Serialize;

use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class SymfonyDtoDeserializer implements DtoDeserializerInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ?string $defaultGroup = null,
    ) {
    }

    public function denormalize(array $data, string $type): object
    {
        try {
            $reflection = new ReflectionClass($type);
            $context = [];

            if ($this->defaultGroup) {
                $context[AbstractNormalizer::GROUPS] = [$this->defaultGroup];
            }

            // There is a constructor → we use it through the serializer.
            if ($reflection->getConstructor() !== null) {
                // Используем Symfony Serializer через конструктор
                return $this->serializer->denormalize($data, $type, null, $context);
            }

            // Without a constructor → we create the object and set the values directly.
            $object = $reflection->newInstanceWithoutConstructor();

            foreach ($data as $field => $value) {
                $setter = 'set' . ucfirst($field);

                if (method_exists($object, $setter)) {
                    $object->$setter($value);
                    continue;
                }

                // Fallback: direct access to the property if it exists and is public or private (in extreme cases).
                if ($reflection->hasProperty($field)) {
                    $property = $reflection->getProperty($field);
                    $property->setAccessible(true);
                    $property->setValue($object, $value);
                    continue;
                }

                throw new RuntimeException("Error during field mapping: '{$field}' тиа {$type}");
            }

            return $object;
        } catch (ReflectionException $e) {
            throw new RuntimeException("Error during DTO deserialization: {$e->getMessage()}", previous: $e);
        }
    }
}
