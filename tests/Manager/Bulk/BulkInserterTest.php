<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Manager\Bulk;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\DriverException as DbalDriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException as UniqueConstraintViolationDbalException;
use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Exception\UniqueConstraintViolationException;
use ITech\Bundle\DbalBundle\Exception\WriteDbalException;
use ITech\Bundle\DbalBundle\Manager\Bulk\BulkInserter;
use ITech\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;
use PHPUnit\Framework\TestCase;

final class BulkInserterTest extends TestCase
{
    private BulkInserter $inserter;
    private Connection $connection;
    private SqlBuilderInterface $builder;

    public function testInsertReturnsZeroOnEmptyInput(): void
    {
        $result = $this->inserter->insert('users', []);
        $this->assertSame(0, $result);
    }

    public function testInsertThrowsOnMismatchedFieldKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row #1 has mismatched fields');

        $this->inserter->insert('users', [
            ['name' => ['Alice']],
            ['email' => ['alice@example.com']],
        ]);
    }

    public function testInsertReturnsRowCount(): void
    {
        $paramsList = [['name' => ['Test']]];
        $sql = 'INSERT INTO users (name) VALUES (?)';
        $flatParams = ['id1', '2025-01-01 00:00:00', '2025-01-01 00:00:00', 'Test'];
        $types = [null, null, null, null];

        $this->builder->method('getInsertBulkSql')->willReturn($sql);
        $this->builder->method('prepareBulkParameterLists')->willReturn([$flatParams, $types]);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($sql, $flatParams, $types)
            ->willReturn(1);

        $result = $this->inserter->insert('users', $paramsList);
        $this->assertEquals(1, $result);
    }

    public function testInsertHandlesUniqueConstraintViolation(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        $exception = new UniqueConstraintViolationDbalException(
            new Exception("Duplicate entry 'A-B' for key 'db.key_name'"),
            null,
        );

        $this->builder->method('getInsertBulkSql')->willReturn('INSERT ...');
        $this->builder->method('prepareBulkParameterLists')->willReturn([[], []]);

        $this->connection
            ->method('executeStatement')
            ->willThrowException($exception);

        $this->inserter->insert('users', [['name' => ['Test']]]);
    }

    public function testInsertHandlesCheckConstraintViolation(): void
    {
        $this->expectException(ConstraintViolationException::class);

        $dbalDriverException = new DriverException(
            new Exception("Check constraint 'chk_limit' is violated"),
            null,
        );

        $this->builder->method('getInsertBulkSql')->willReturn('INSERT ...');
        $this->builder->method('prepareBulkParameterLists')->willReturn([[], []]);

        $this->connection
            ->method('executeStatement')
            ->willThrowException($dbalDriverException);

        $this->inserter->insert('users', [['name' => ['Test']]]);
    }

    public function testInsertHandlesGenericDbalException(): void
    {
        $this->expectException(WriteDbalException::class);

        $genericDbalException = new DbalDriverException(
            new Exception('Some DBAL error'),
            null,
        );

        $this->builder->method('getInsertBulkSql')->willReturn('INSERT ...');
        $this->builder->method('prepareBulkParameterLists')->willReturn([[], []]);

        $this->connection
            ->method('executeStatement')
            ->willThrowException($genericDbalException);

        $this->inserter->insert('users', [['name' => ['Test']]]);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->builder = $this->createMock(SqlBuilderInterface::class);

        $config = new DbalBundleConfig(
            fieldNames: [
                BundleConfigurationInterface::ID_NAME => 'id',
                BundleConfigurationInterface::CREATED_AT_NAME => 'created_at',
                BundleConfigurationInterface::UPDATED_AT_NAME => 'updated_at',
            ],
            useAutoMapper: false,
            defaultDtoGroup: '',
            chunkSize: 1000,
            orderDirection: 'ASC',
        );

        $this->inserter = new BulkInserter($this->connection, $config, $this->builder);
    }
}
