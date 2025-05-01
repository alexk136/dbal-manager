<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Tests\Manager\Iterator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Elrise\Bundle\DbalBundle\Config\DbalBundleConfig;
use Elrise\Bundle\DbalBundle\Manager\Iterator\OffsetIterator;
use Elrise\Bundle\DbalBundle\Service\Serialize\DtoDeserializerInterface;
use PHPUnit\Framework\TestCase;

#[CoversClass(OffsetIterator::class)]
final class OffsetIteratorTest extends TestCase
{
    public function testIterateWithRawData(): void
    {
        $sql = 'SELECT * FROM test_table';
        $data1 = [
            ['id' => 1, 'name' => 'first'],
            ['id' => 2, 'name' => 'second'],
        ];
        $data2 = [
            ['id' => 3, 'name' => 'third'],
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls($data1, $data2, []);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $deserializer = $this->createMock(DtoDeserializerInterface::class);

        $config = new DbalBundleConfig(chunkSize: 2);

        $iterator = new OffsetIterator($connection, $deserializer, $config);

        $collected = [];

        foreach ($iterator->iterate($sql) as $item) {
            $collected[] = $item;
        }

        $this->assertCount(3, $collected);
        $this->assertEquals('first', $collected[0]['name']);
        $this->assertEquals('second', $collected[1]['name']);
        $this->assertEquals('third', $collected[2]['name']);
    }
}
