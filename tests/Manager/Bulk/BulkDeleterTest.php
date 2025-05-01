<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Tests\Manager\Bulk;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UnknownDriver;
use Doctrine\DBAL\ParameterType;
use Elrise\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use Elrise\Bundle\DbalBundle\Config\DbalBundleConfig;
use Elrise\Bundle\DbalBundle\Exception\WriteDbalException;
use Elrise\Bundle\DbalBundle\Manager\Bulk\BulkDeleter;
use Elrise\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use Elrise\Bundle\DbalBundle\Sql\Builder\PostgresSqlBuilder;
use Elrise\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;
use Elrise\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkDeleter::class)]
final class BulkDeleterTest extends TestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i';

    private MockObject $connection;

    #[DataProvider('sqlBuildersProviderDeleteMany')]
    public function testDeleteManyReturnsRowCount(
        SqlBuilderInterface $sqlBuilder,
        array $ids,
        string $expectedSql,
        array $expectedParams,
        array $expectedTypes,
        int $expectedAffected,
    ): void {
        $deleter = $this->createDeleter($sqlBuilder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedParams, $expectedTypes)
            ->willReturn($expectedAffected);

        $result = $deleter->deleteMany('users', $ids);

        $this->assertSame($expectedAffected, $result);
    }

    #[DataProvider('sqlBuildersProviderDeleteEmpty')]
    public function testDeleteManyReturnsZeroOnEmptyInput(SqlBuilderInterface $sqlBuilder): void
    {
        $deleter = $this->createDeleter($sqlBuilder);

        $result = $deleter->deleteMany('users', []);

        $this->assertSame(0, $result);
    }

    #[DataProvider('sqlBuildersProviderDeleteOne')]
    public function testDeleteOneDelegatesToDeleteMany(
        SqlBuilderInterface $sqlBuilder,
        int $id,
        string $expectedSql,
        array $expectedFlatParams,
        array $expectedTypes,
        int $expectedAffected,
    ): void {
        $deleter = $this->createDeleter($sqlBuilder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedFlatParams, $expectedTypes)
            ->willReturn($expectedAffected);

        $result = $deleter->deleteOne('users', $id);

        $this->assertSame($expectedAffected, $result);
    }

    #[DataProvider('sqlBuildersProviderDeleteException')]
    public function testDeleteHandlesGenericDbalException(
        SqlBuilderInterface $sqlBuilder,
        array $ids,
        string $expectedSql,
        array $expectedFlatParams,
        array $expectedTypes,
    ): void {
        $this->expectException(WriteDbalException::class);

        $deleter = $this->createDeleter($sqlBuilder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedFlatParams, $expectedTypes)
            ->willThrowException(new UnknownDriver('Generic DBAL exception'));

        $deleter->deleteMany('users', $ids);
    }

    #[DataProvider('sqlBuildersProviderSoftDeleteMany')]
    public function testSoftDeleteManyUpdatesDeletedAtForGivenIds(
        SqlBuilderInterface $sqlBuilder,
        array $ids,
        string $expectedSql,
        array $expectedFlatParams,
        array $expectedTypes,
        int $expectedAffected,
    ): void {
        $deleter = $this->createDeleter($sqlBuilder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedFlatParams, $expectedTypes)
            ->willReturn($expectedAffected);

        $result = $deleter->deleteSoftMany('users', $ids);

        $this->assertSame($expectedAffected, $result);
    }

    #[DataProvider('sqlBuildersProviderDeleteSoftOne')]
    public function testDeleteSoftOneDelegatesToDeleteSoftMany(
        SqlBuilderInterface $sqlBuilder,
        int $id,
        string $expectedSql,
        array $expectedFlatParams,
        array $expectedTypes,
        int $expectedAffected,
    ): void {
        $deleter = $this->createDeleter($sqlBuilder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedFlatParams, $expectedTypes)
            ->willReturn($expectedAffected);

        $result = $deleter->deleteSoftOne('users', $id);

        $this->assertSame($expectedAffected, $result);
    }

    public static function sqlBuildersProviderDeleteMany(): array
    {
        return [
            'mysql' => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [1, 2, 3],
                'DELETE FROM `users` WHERE `id` IN (?, ?, ?)',
                [1, 2, 3],
                [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::INTEGER],
                3,
            ],
            'postgres' => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [1, 2, 3],
                'DELETE FROM "users" WHERE "id" IN (?, ?, ?)',
                [1, 2, 3],
                [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::INTEGER],
                3,
            ],
        ];
    }

    public static function sqlBuildersProviderDeleteEmpty(): array
    {
        return [
            'mysql' => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
            ],
            'postgres' => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
            ],
        ];
    }

    public static function sqlBuildersProviderDeleteOne(): array
    {
        return [
            'mysql' => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                42,
                'DELETE FROM `users` WHERE `id` IN (?)',
                [42],
                [ParameterType::INTEGER],
                1,
            ],
            'postgres' => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                42,
                'DELETE FROM "users" WHERE "id" IN (?)',
                [42],
                [ParameterType::INTEGER],
                1,
            ],
        ];
    }

    public static function sqlBuildersProviderDeleteException(): array
    {
        return [
            'mysql' => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [5],
                'DELETE FROM `users` WHERE `id` IN (?)',
                [5],
                [ParameterType::INTEGER],
            ],
            'postgres' => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [5],
                'DELETE FROM "users" WHERE "id" IN (?)',
                [5],
                [ParameterType::INTEGER],
            ],
        ];
    }

    public static function sqlBuildersProviderSoftDeleteMany(): array
    {
        $now = date(self::DATE_FORMAT);

        return [
            'mysql' => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [1, 2, 3],
                'UPDATE `users` SET `deleted_at` = CASE WHEN (`id` = ?) THEN ? WHEN (`id` = ?) THEN ? WHEN (`id` = ?) THEN ? ELSE `deleted_at` END WHERE (`id` = ?) OR (`id` = ?) OR (`id` = ?)',
                [
                    1, $now,
                    2, $now,
                    3, $now,
                    1, 2, 3,
                ],
                [
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::INTEGER,
                ],
                3,
            ],
            'postgres' => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [1, 2, 3],
                'UPDATE "users" SET "deleted_at" = CASE WHEN ("id" = ?) THEN ? WHEN ("id" = ?) THEN ? WHEN ("id" = ?) THEN ? ELSE "deleted_at" END WHERE ("id" = ?) OR ("id" = ?) OR ("id" = ?)',
                [
                    1, $now,
                    2, $now,
                    3, $now,
                    1, 2, 3,
                ],
                [
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::INTEGER,
                ],
                3,
            ],
        ];
    }

    public static function sqlBuildersProviderDeleteSoftOne(): array
    {
        $now = date(self::DATE_FORMAT);

        return [
            'mysql' => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                42,
                'UPDATE `users` SET `deleted_at` = CASE WHEN (`id` = ?) THEN ? ELSE `deleted_at` END WHERE (`id` = ?)',
                [42, $now, 42],
                [ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER],
                1,
            ],
            'postgres' => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                42,
                'UPDATE "users" SET "deleted_at" = CASE WHEN ("id" = ?) THEN ? ELSE "deleted_at" END WHERE ("id" = ?)',
                [42, $now, 42],
                [ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER],
                1,
            ],
        ];
    }

    private function createDeleter(SqlBuilderInterface $sqlBuilder): BulkDeleter
    {
        $this->connection = $this->createMock(Connection::class);

        return new BulkDeleter($this->connection, $sqlBuilder, $this->createConfig());
    }

    private function createConfig(): DbalBundleConfig
    {
        return new DbalBundleConfig(
            fieldNames: [
                BundleConfigurationInterface::ID_NAME => 'id',
                BundleConfigurationInterface::CREATED_AT_NAME => 'created_at',
                BundleConfigurationInterface::UPDATED_AT_NAME => 'updated_at',
                BundleConfigurationInterface::DELETED_AT_NAME => 'deleted_at',
            ],
            defaultDateTimeFormat: self::DATE_FORMAT,
        );
    }
}
