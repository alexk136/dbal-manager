<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Manager\Iterator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Manager\Iterator\CursorIterator;
use ITech\Bundle\DbalBundle\Service\Serialize\DtoDeserializerInterface;
use PHPUnit\Framework\TestCase;

class CursorIteratorTest extends TestCase
{
    public function testIterateWithRawData(): void
    {
        $tableName = 'test_table';
        $cursorField = 'id';
        $data = [
            ['id' => 1, 'name' => 'first'],
            ['id' => 2, 'name' => 'second'],
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls($data, []);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $deserializer = $this->createMock(DtoDeserializerInterface::class);

        $config = new DbalBundleConfig(chunkSize: 100);

        $iterator = new CursorIterator($connection, $deserializer, $config);

        $collected = [];

        foreach ($iterator->iterate($tableName, $cursorField) as $item) {
            $collected[] = $item;
        }

        $this->assertCount(2, $collected);
        $this->assertEquals($data[0], $collected[0]);
        $this->assertEquals($data[1], $collected[1]);
    }
}
