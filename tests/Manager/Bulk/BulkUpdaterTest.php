<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Manager\Bulk;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Exception;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Manager\Bulk\BulkUpdater;
use ITech\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use ITech\Bundle\DbalBundle\Sql\Builder\PostgresSqlBuilder;
use ITech\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkUpdater::class)]
final class BulkUpdaterTest extends TestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i';

    private MockObject $connection;

    #[DataProvider('sqlBuildersProvider')]
    public function testUpdateReturnsZeroOnEmptyInput(SqlBuilderInterface $sqlBuilder): void
    {
        $updater = $this->createUpdater($sqlBuilder);

        $this->connection
            ->expects($this->never())
            ->method('executeStatement');

        $result = $updater->updateMany('users', []);
        $this->assertSame(0, $result);
    }

    #[DataProvider('sqlBuildersProviderWhereFieldsNotProvided')]
    public function testUpdateUsesDefaultIdWhenWhereFieldsNotProvided(
        SqlBuilderInterface $sqlBuilder,
        array $paramsList,
        string $expectedSql,
        array $expectedFlatParams,
        array $expectedTypes,
    ): void {
        $updater = $this->createUpdater($sqlBuilder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedFlatParams, $expectedTypes)
            ->willReturn(1);

        $result = $updater->updateMany('users', $paramsList);

        $this->assertSame(1, $result);
    }

    #[DataProvider('sqlBuildersProviderWithWhereFieldsProvided')]
    public function testUpdateUsesProvidedWhereFields(
        SqlBuilderInterface $sqlBuilder,
        array $paramsList,
        array $whereFields,
        string $expectedSql,
        array $expectedFlatParams,
        array $expectedTypes,
    ): void {
        $updater = $this->createUpdater($sqlBuilder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedFlatParams, $expectedTypes)
            ->willReturn(1);

        $result = $updater->updateMany('users', $paramsList, $whereFields);

        $this->assertSame(1, $result);
    }

    #[DataProvider('sqlBuildersProviderThrowsException')]
    public function testUpdateThrowsExceptionOnQueryError(
        SqlBuilderInterface $sqlBuilder,
        array $paramsList,
        array $whereFields,
        string $expectedSql,
        array $expectedFlatParams,
        array $expectedTypes,
    ): void {
        $this->expectException(Exception::class);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedFlatParams, $expectedTypes)
            ->willThrowException(new Exception('Simulated failure'));

        $updater = new BulkUpdater($connection, $sqlBuilder, $this->createConfig());

        $updater->updateMany('users', $paramsList, $whereFields);
    }

    #[DataProvider('sqlBuildersProviderManyRecords')]
    public function testUpdateManyRecords(
        SqlBuilderInterface $sqlBuilder,
        array $paramsList,
        array $whereFields,
        string $expectedSql,
        array $expectedFlatParams,
        array $expectedTypes,
    ): void {
        $updater = $this->createUpdater($sqlBuilder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedFlatParams, $expectedTypes)
            ->willReturn(2);

        $result = $updater->updateMany('users', $paramsList, $whereFields);

        $this->assertSame(2, $result);
    }

    public static function sqlBuildersProvider(): array
    {
        return [
            MysqlSqlBuilder::class => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
            ],
            PostgresSqlBuilder::class => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
            ],
        ];
    }

    public static function sqlBuildersProviderWhereFieldsNotProvided(): array
    {
        $now = date(self::DATE_FORMAT);

        return [
            MysqlSqlBuilder::class => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [['id' => 123, 'name' => 'Alice']],
                'UPDATE `users` SET `name` = CASE WHEN (`id` = ?) THEN ? ELSE `name` END, `updated_at` = CASE WHEN (`id` = ?) THEN ? ELSE `updated_at` END WHERE (`id` = ?)',
                [123, 'Alice', 123, $now, 123],
                [ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER],
            ],
            PostgresSqlBuilder::class => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [['id' => 123, 'name' => 'Alice']],
                'UPDATE "users" SET "name" = CASE WHEN ("id" = ?) THEN ? ELSE "name" END, "updated_at" = CASE WHEN ("id" = ?) THEN ? ELSE "updated_at" END WHERE ("id" = ?)',
                [123, 'Alice', 123, $now, 123],
                [ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER],
            ],
        ];
    }

    public static function sqlBuildersProviderWithWhereFieldsProvided(): array
    {
        $now = date(self::DATE_FORMAT);

        return [
            MysqlSqlBuilder::class => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [['email' => 'test@example.com', 'name' => 'Alice']],
                ['email'],
                'UPDATE `users` SET `name` = CASE WHEN (`email` = ?) THEN ? ELSE `name` END, `updated_at` = CASE WHEN (`email` = ?) THEN ? ELSE `updated_at` END WHERE (`email` = ?)',
                ['test@example.com', 'Alice', 'test@example.com', $now, 'test@example.com'],
                [ParameterType::STRING, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING],
            ],
            PostgresSqlBuilder::class => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [['email' => 'test@example.com', 'name' => 'Alice']],
                ['email'],
                'UPDATE "users" SET "name" = CASE WHEN ("email" = ?) THEN ? ELSE "name" END, "updated_at" = CASE WHEN ("email" = ?) THEN ? ELSE "updated_at" END WHERE ("email" = ?)',
                ['test@example.com', 'Alice', 'test@example.com', $now, 'test@example.com'],
                [ParameterType::STRING, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING],
            ],
        ];
    }

    public static function sqlBuildersProviderThrowsException(): array
    {
        $now = date(self::DATE_FORMAT);

        return [
            MysqlSqlBuilder::class => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [['id' => 123, 'name' => 'Alice']],
                ['id'],
                'UPDATE `users` SET `name` = CASE WHEN (`id` = ?) THEN ? ELSE `name` END, `updated_at` = CASE WHEN (`id` = ?) THEN ? ELSE `updated_at` END WHERE (`id` = ?)',
                [123, 'Alice', 123, $now, 123],
                [ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER],
            ],
            PostgresSqlBuilder::class => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [['id' => 123, 'name' => 'Alice']],
                ['id'],
                'UPDATE "users" SET "name" = CASE WHEN ("id" = ?) THEN ? ELSE "name" END, "updated_at" = CASE WHEN ("id" = ?) THEN ? ELSE "updated_at" END WHERE ("id" = ?)',
                [123, 'Alice', 123, $now, 123],
                [ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER],
            ],
        ];
    }

    public static function sqlBuildersProviderManyRecords(): array
    {
        $now = date(self::DATE_FORMAT);

        return [
            MysqlSqlBuilder::class => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [
                    ['id' => 1, 'name' => 'Alice'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                ['id'],
                'UPDATE `users` SET `name` = CASE WHEN (`id` = ?) THEN ? WHEN (`id` = ?) THEN ? ELSE `name` END, `updated_at` = CASE WHEN (`id` = ?) THEN ? WHEN (`id` = ?) THEN ? ELSE `updated_at` END WHERE (`id` = ?) OR (`id` = ?)',
                [
                    1, 'Alice',
                    2, 'Bob',
                    1, $now,
                    2, $now,
                    1, 2,
                ],
                [
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::INTEGER,
                ],
            ],
            PostgresSqlBuilder::class => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [
                    ['id' => 1, 'name' => 'Alice'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                ['id'],
                'UPDATE "users" SET "name" = CASE WHEN ("id" = ?) THEN ? WHEN ("id" = ?) THEN ? ELSE "name" END, "updated_at" = CASE WHEN ("id" = ?) THEN ? WHEN ("id" = ?) THEN ? ELSE "updated_at" END WHERE ("id" = ?) OR ("id" = ?)',
                [
                    1, 'Alice',
                    2, 'Bob',
                    1, $now,
                    2, $now,
                    1, 2,
                ],
                [
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::INTEGER,
                ],
            ],
        ];
    }

    private function createUpdater(SqlBuilderInterface $sqlBuilder): BulkUpdater
    {
        $this->connection = $this->createMock(Connection::class);

        return new BulkUpdater($this->connection, $sqlBuilder, $this->createConfig());
    }

    private function createConfig(): DbalBundleConfig
    {
        return new DbalBundleConfig(
            fieldNames: [
                BundleConfigurationInterface::ID_NAME => 'id',
                BundleConfigurationInterface::CREATED_AT_NAME => 'created_at',
                BundleConfigurationInterface::UPDATED_AT_NAME => 'updated_at',
            ],
            defaultDateTimeFormat: self::DATE_FORMAT,
        );
    }
}
