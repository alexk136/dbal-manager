<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Utils;

use ITech\Bundle\DbalBundle\Service\Serialize\SymfonyDtoDeserializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SymfonyDtoDeserializerTest extends TestCase
{
    private SymfonyDtoDeserializer $deserializer;

    public function testDeserializationWithConstructor(): void
    {
        $data = ['id' => 'abc123', 'count' => 42];
        $dto = $this->deserializer->denormalize($data, ConstructorDto::class);

        $this->assertInstanceOf(ConstructorDto::class, $dto);
        $this->assertSame('abc123', $dto->id);
        $this->assertSame(42, $dto->count);
    }

    public function testDeserializationWithPublicProperties(): void
    {
        $data = ['id' => 'xyz789', 'count' => 99];
        $dto = $this->deserializer->denormalize($data, PublicPropertyDto::class);

        $this->assertInstanceOf(PublicPropertyDto::class, $dto);
        $this->assertSame('xyz789', $dto->id);
        $this->assertSame(99, $dto->count);
    }

    public function testDeserializationWithPrivateProperties(): void
    {
        $data = ['id' => 'secret', 'count' => 123];
        $dto = $this->deserializer->denormalize($data, PrivatePropertyDto::class);

        $this->assertInstanceOf(PrivatePropertyDto::class, $dto);
        $this->assertSame('secret', $dto->getId());
        $this->assertSame(123, $dto->getCount());
    }

    protected function setUp(): void
    {
        $normalizer = new ObjectNormalizer(null, null, new PropertyAccessor(), new ReflectionExtractor());
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);
        $this->deserializer = new SymfonyDtoDeserializer($serializer);
    }
}

final class ConstructorDto
{
    public function __construct(
        public string $id,
        public int $count,
    ) {
    }
}

final class PublicPropertyDto
{
    public string $id;
    public int $count;
}

final class PrivatePropertyDto
{
    private string $id;
    private int $count;

    public function getId(): string
    {
        return $this->id;
    }
    public function getCount(): int
    {
        return $this->count;
    }
}
