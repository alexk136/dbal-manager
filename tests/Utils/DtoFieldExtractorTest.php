<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Strategy;

use ITech\Bundle\DbalBundle\Utils\DtoFieldExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DtoFieldExtractor::class)]
final class DtoFieldExtractorTest extends TestCase
{
    public function testGetFieldsFromPublicProperties(): void
    {
        $fields = DtoFieldExtractor::getFields(DtoWithPublicProperties::class);

        $this->assertEqualsCanonicalizing(['id', 'email'], $fields);
    }

    public function testGetFieldsFromConstructor(): void
    {
        $fields = DtoFieldExtractor::getFields(DtoWithConstructorProperties::class);

        $this->assertEqualsCanonicalizing(['id', 'name'], $fields);
    }

    public function testGetFieldsFromGetters(): void
    {
        $fields = DtoFieldExtractor::getFields(DtoWithGetters::class);

        $this->assertEqualsCanonicalizing(['id', 'email', 'active'], $fields);
    }

    public function testNoDuplicatesWithMixedAccess(): void
    {
        $fields = DtoFieldExtractor::getFields(DtoWithEverything::class);

        $this->assertEqualsCanonicalizing(['id', 'email', 'active'], $fields);
    }
}

final class DtoWithPublicProperties
{
    public string $id;
    public string $email;
}

final class DtoWithConstructorProperties
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}

final class DtoWithGetters
{
    public function getId(): string
    {
        return '';
    }
    public function getEmail(): string
    {
        return '';
    }
    public function isActive(): bool
    {
        return true;
    }
}

final class DtoWithEverything
{
    public string $id;

    public function __construct(
        public string $email,
    ) {
    }

    public function isActive(): bool
    {
        return true;
    }
}
