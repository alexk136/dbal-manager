<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Tests\Utils;

use Elrise\Bundle\DbalBundle\Utils\IdGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IdGenerator::class)]
class IdGeneratorTest extends TestCase
{
    public function testGenerateUniqueIdReturnsNonEmptyString(): void
    {
        $id = IdGenerator::generateUniqueId();

        $this->assertIsString($id, 'Generated ID should be a string');
        $this->assertNotEmpty($id, 'Generated ID should not be empty');
        $this->assertMatchesRegularExpression('/^\d{5}[a-f0-9]{13}$/', $id, 'ID format should match expected pattern');
    }

    public function testGenerateUniqueIdIsUnique(): void
    {
        $ids = [];

        for ($i = 0; $i < 100; ++$i) {
            $id = IdGenerator::generateUniqueId();
            $this->assertArrayNotHasKey($id, $ids, "Duplicate ID generated: $id");
            $ids[$id] = true;
        }
    }
}
