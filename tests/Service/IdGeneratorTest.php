<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Service;

use ITech\Bundle\DbalBundle\Service\Generator\IdGenerator;
use PHPUnit\Framework\TestCase;

class IdGeneratorTest extends TestCase
{
    private IdGenerator $generator;

    public function testGenerateUniqueIdReturnsNonEmptyString(): void
    {
        $id = $this->generator->generateUniqueId();

        $this->assertIsString($id, 'Generated ID should be a string');
        $this->assertNotEmpty($id, 'Generated ID should not be empty');
        $this->assertMatchesRegularExpression('/^\d{5}[a-f0-9]{13}$/', $id, 'ID format should match expected pattern');
    }

    public function testGenerateUniqueIdIsUnique(): void
    {
        $ids = [];

        for ($i = 0; $i < 100; ++$i) {
            $id = $this->generator->generateUniqueId();
            $this->assertArrayNotHasKey($id, $ids, "Duplicate ID generated: $id");
            $ids[$id] = true;
        }
    }

    public function testGenerateThreadIdReturnsValidRange(): void
    {
        $maxThread = 10;

        for ($i = 0; $i < 100; ++$i) {
            $threadId = $this->generator->generateThreadId($maxThread);
            $this->assertGreaterThanOrEqual(1, $threadId, 'Thread ID should be >= 1');
            $this->assertLessThanOrEqual($maxThread, $threadId, "Thread ID should be <= $maxThread");
        }
    }

    public function testGenerateThreadByIdReturnsConsistentThreadId(): void
    {
        $maxThread = 8;
        $id = '1234567890abcdef';

        $threadId1 = $this->generator->generateThreadById($maxThread, $id);
        $threadId2 = $this->generator->generateThreadById($maxThread, $id);

        $this->assertEquals($threadId1, $threadId2, 'Thread ID should be deterministic for the same input');
        $this->assertGreaterThanOrEqual(1, $threadId1, 'Thread ID should be >= 1');
        $this->assertLessThanOrEqual($maxThread, $threadId1, "Thread ID should be <= $maxThread");
    }

    public function testGenerateThreadByIdHandlesNonNumericCharacters(): void
    {
        $maxThread = 5;
        $idWithLetters = 'abc123xyz';

        $threadId = $this->generator->generateThreadById($maxThread, $idWithLetters);

        $this->assertGreaterThanOrEqual(1, $threadId, 'Thread ID should be >= 1');
        $this->assertLessThanOrEqual($maxThread, $threadId, "Thread ID should be <= $maxThread");
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new IdGenerator();
    }
}
