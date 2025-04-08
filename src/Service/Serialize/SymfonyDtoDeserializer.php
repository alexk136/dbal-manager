<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Service\Serialize;

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

            // Есть конструктор → используем через сериализатор
            if ($reflection->getConstructor() !== null) {
                // Используем Symfony Serializer через конструктор
                return $this->serializer->denormalize($data, $type, null, $context);
            }

            // Без конструктора → создаем объект и проставляем значения напрямую
            $object = $reflection->newInstanceWithoutConstructor();

            foreach ($data as $field => $value) {
                $setter = 'set' . ucfirst($field);

                if (method_exists($object, $setter)) {
                    $object->$setter($value);
                    continue;
                }

                // fallback: прямой доступ к свойству, если оно есть и публичное или приватное (в крайних случаях)
                if ($reflection->hasProperty($field)) {
                    $property = $reflection->getProperty($field);
                    $property->setAccessible(true);
                    $property->setValue($object, $value);
                    continue;
                }

                throw new RuntimeException("Ошибка при мапинге поля: '{$field}' тиа {$type}");
            }

            return $object;
        } catch (ReflectionException $e) {
            throw new RuntimeException("Ошибка при десериализации DTO: {$e->getMessage()}", previous: $e);
        }
    }
}
