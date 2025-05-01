<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Tests\Manager\Bulk;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Exception\DriverException as DbalDriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException as UniqueConstraintViolationDbalException;
use Doctrine\DBAL\ParameterType;
use Elrise\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use Elrise\Bundle\DbalBundle\Config\DbalBundleConfig;
use Elrise\Bundle\DbalBundle\Exception\UniqueConstraintViolationException;
use Elrise\Bundle\DbalBundle\Exception\WriteDbalException;
use Elrise\Bundle\DbalBundle\Manager\Bulk\BulkInserter;
use Elrise\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use Elrise\Bundle\DbalBundle\Sql\Builder\PostgresSqlBuilder;
use Elrise\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;
use Elrise\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkInserter::class)]
final class BulkInserterTest extends TestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i';

    private Connection $connection;

    #[DataProvider('sqlBuildersProviderInsertMany')]
    public function testInsertReturnsRowCount(
        SqlBuilderInterface $builder,
        array $paramsList,
        string $expectedSql,
        array $expectedParams,
        array $expectedTypes,
        int $expectedAffected,
    ): void {
        $inserter = $this->createInserter($builder);

        $this->connection
            ->method('executeStatement')
            ->with($expectedSql, $expectedParams, $expectedTypes)
            ->willReturn($expectedAffected);

        $result = $inserter->insertMany('users', $paramsList);

        $this->assertSame($expectedAffected, $result);
    }

    public function testInsertReturnsZeroOnEmptyInput(): void
    {
        $inserter = $this->createInserter(new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()));

        $result = $inserter->insertMany('users', []);
        $this->assertSame(0, $result);
    }

    public function testInsertThrowsOnMismatchedFieldKeys(): void
    {
        $inserter = $this->createInserter(new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()));

        $this->expectException(InvalidArgumentException::class);

        $inserter->insertMany('users', [
            ['name' => ['Alice']],
            ['email' => ['alice@example.com']],
        ]);
    }

    #[DataProvider('sqlBuildersProviders')]
    public function testInsertHandlesUniqueConstraintViolation(SqlBuilderInterface $builder): void
    {
        $inserter = $this->createInserter($builder);

        $this->expectException(UniqueConstraintViolationException::class);

        $exception = new UniqueConstraintViolationDbalException(
            new Exception("Duplicate entry 'A-B' for key 'db.key_name'"),
            null,
        );

        $this->connection
            ->method('executeStatement')
            ->willThrowException($exception);

        $inserter->insertMany('users', [['name' => ['Test']]]);
    }

    #[DataProvider('sqlBuildersProviders')]
    public function testInsertHandlesCheckConstraintViolation(SqlBuilderInterface $builder): void
    {
        $inserter = $this->createInserter($builder);

        $this->expectException(WriteDbalException::class);

        $dbalDriverException = new DbalDriverException(
            new Exception('Check constraint "chk_limit" is violated'),
            null,
        );

        $this->connection
            ->method('executeStatement')
            ->willThrowException($dbalDriverException);

        $inserter->insertMany('users', [['name' => ['Test']]]);
    }

    #[DataProvider('sqlBuildersProviders')]
    public function testInsertHandlesGenericDbalException(SqlBuilderInterface $builder): void
    {
        $inserter = $this->createInserter($builder);

        $this->expectException(WriteDbalException::class);

        $this->connection
            ->method('executeStatement')
            ->willThrowException(new DbalDriverException(
                new Exception('Some DBAL error'),
                null,
            ));

        $inserter->insertMany('users', [['name' => ['Test']]]);
    }

    public static function sqlBuildersProviderInsertMany(): array
    {
        $now = date(self::DATE_FORMAT);

        return [
            'mysql' => [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [
                    ['name' => ['Test1']],
                    ['name' => ['Test2']],
                ],
                'INSERT INTO `users` (`name`, `created_at`, `updated_at`) VALUES (?, ?, ?), (?, ?, ?)',
                ['Test1', $now, $now, 'Test2', $now, $now],
                [
                    ParameterType::STRING, ParameterType::STRING, ParameterType::STRING,
                    ParameterType::STRING, ParameterType::STRING, ParameterType::STRING,
                ],
                2,
            ],
            'postgres' => [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [
                    ['name' => ['Test1']],
                    ['name' => ['Test2']],
                ],
                'INSERT INTO "users" ("name", "created_at", "updated_at") VALUES (?, ?, ?), (?, ?, ?)',
                ['Test1', $now, $now, 'Test2', $now, $now],
                [
                    ParameterType::STRING, ParameterType::STRING, ParameterType::STRING,
                    ParameterType::STRING, ParameterType::STRING, ParameterType::STRING,
                ],
                2,
            ],
        ];
    }

    public static function sqlBuildersProviders(): array
    {
        return [
            'mysql' => [new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy())],
            'postgres' => [new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy())],
        ];
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
    }

    private function createInserter(SqlBuilderInterface $builder): BulkInserter
    {
        return new BulkInserter(
            $this->connection,
            $builder,
            new DbalBundleConfig(
                fieldNames: [
                    BundleConfigurationInterface::ID_NAME => 'id',
                    BundleConfigurationInterface::CREATED_AT_NAME => 'created_at',
                    BundleConfigurationInterface::UPDATED_AT_NAME => 'updated_at',
                ],
                defaultDateTimeFormat: self::DATE_FORMAT,
            ),
        );
    }
}
