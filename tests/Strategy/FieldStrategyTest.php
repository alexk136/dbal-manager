<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Strategy;

use ITech\Bundle\DbalBundle\Service\Dto\DtoFieldExtractor;
use PHPUnit\Framework\TestCase;

final class FieldStrategyTest extends TestCase
{
    private DtoFieldExtractor $strategy;

    public function testGetFieldsFromPublicProperties(): void
    {
        $fields = $this->strategy->getFields(DtoWithPublicProperties::class);

        $this->assertEqualsCanonicalizing(['id', 'email'], $fields);
    }

    public function testGetFieldsFromConstructor(): void
    {
        $fields = $this->strategy->getFields(DtoWithConstructorProperties::class);

        $this->assertEqualsCanonicalizing(['id', 'name'], $fields);
    }

    public function testGetFieldsFromGetters(): void
    {
        $fields = $this->strategy->getFields(DtoWithGetters::class);

        $this->assertEqualsCanonicalizing(['id', 'email', 'active'], $fields);
    }

    public function testNoDuplicatesWithMixedAccess(): void
    {
        $fields = $this->strategy->getFields(DtoWithEverything::class);

        $this->assertEqualsCanonicalizing(['id', 'email', 'active'], $fields);
    }

    protected function setUp(): void
    {
        $this->strategy = new DtoFieldExtractor();
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
