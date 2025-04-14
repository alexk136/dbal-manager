<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Manager\Bulk;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Exception\DriverException as DbalDriverException;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Exception\WriteDbalException;
use ITech\Bundle\DbalBundle\Manager\Bulk\BulkDeleter;
use ITech\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;

use PDO;
use PHPUnit\Framework\TestCase;

final class BulkDeleterTest extends TestCase
{
    private BulkDeleter $deleter;
    private Connection $connection;
    private SqlBuilderInterface $builder;

    public function testDeleteManyReturnsZeroOnEmptyInput(): void
    {
        $result = $this->deleter->deleteMany('users', []);
        $this->assertSame(0, $result);
    }

    public function testDeleteManyReturnsRowCount(): void
    {
        $ids = [1, 2, 3];
        $sql = 'DELETE FROM `users` WHERE `id` IN (?, ?, ?)';
        $flatParams = [1, 2, 3];
        $types = [PDO::PARAM_INT, PDO::PARAM_INT, PDO::PARAM_INT];

        $this->builder->method('getDeleteBulkSql')->with('users', $ids)->willReturn($sql);
        $this->builder->method('prepareBulkParameterLists')->willReturn([$flatParams, $types]);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($sql, $flatParams, $types)
            ->willReturn(3);

        $result = $this->deleter->deleteMany('users', $ids);
        $this->assertSame(3, $result);
    }

    public function testDeleteOneDelegatesToDeleteMany(): void
    {
        $sql = 'DELETE FROM `users` WHERE `id` IN (?)';
        $flatParams = [42];
        $types = [PDO::PARAM_INT];

        $this->builder->method('getDeleteBulkSql')->with('users', [42])->willReturn($sql);
        $this->builder->method('prepareBulkParameterLists')->willReturn([$flatParams, $types]);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($sql, $flatParams, $types)
            ->willReturn(1);

        $result = $this->deleter->deleteOne('users', 42);
        $this->assertSame(1, $result);
    }

    public function testDeleteHandlesGenericDbalException(): void
    {
        $this->expectException(WriteDbalException::class);

        $ids = [5];
        $sql = 'DELETE FROM `users` WHERE `id` IN (?)';

        $this->builder->method('getDeleteBulkSql')->willReturn($sql);
        $this->builder->method('prepareBulkParameterLists')->willReturn([[$ids[0]], [PDO::PARAM_INT]]);

        $this->connection
            ->method('executeStatement')
            ->willThrowException(new DbalDriverException(
                new Exception('Some DBAL error'),
                null,
            ));

        $this->deleter->deleteMany('users', $ids);
    }

    public function testSoftDeleteManyUpdatesDeletedAtForGivenIds(): void
    {
        $ids = [1, 2, 3];

        $flatParams = [];

        $sql = 'UPDATE `users` SET `deleted_at` = CASE WHEN `id` = ? THEN ? ... END';

        $types = array_fill(0, count($ids) * 2, PDO::PARAM_STR);

        $this->builder->expects($this->once())
            ->method('getUpdateBulkSql')
            ->willReturn($sql);

        $this->builder->expects($this->once())
            ->method('prepareBulkParameterLists')
            ->willReturn([$flatParams, $types]);

        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with($sql, $flatParams, $types)
            ->willReturn(count($ids));

        $result = $this->deleter->deleteSoftMany('users', $ids);
        $this->assertSame(3, $result);
    }

    public function testDeleteSoftOneDelegatesToDeleteSoftMany(): void
    {
        $id = 42;
        $now = '2025-04-13 15:00:00';

        $sql = 'UPDATE `users` SET `deleted_at` = CASE WHEN `id` = ? THEN ? END';
        $flatParams = [$id, $now];
        $types = [PDO::PARAM_INT, PDO::PARAM_STR];

        $this->builder->expects($this->once())
            ->method('getUpdateBulkSql')
            ->willReturn($sql);

        $this->builder->expects($this->once())
            ->method('prepareBulkParameterLists')
            ->willReturn([$flatParams, $types]);

        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with($sql, $flatParams, $types)
            ->willReturn(1);

        $result = $this->deleter->deleteSoftOne('users', $id);
        $this->assertSame(1, $result);
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
                BundleConfigurationInterface::DELETED_AT_NAME => 'deleted_at',
            ],
        );

        $this->deleter = new BulkDeleter($this->connection, $this->builder, $config);
    }
}
