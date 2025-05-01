<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Tests\Manager\Bulk;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Exception\DriverException as DbalDriverException;
use Doctrine\DBAL\ParameterType;
use Elrise\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use Elrise\Bundle\DbalBundle\Config\DbalBundleConfig;
use Elrise\Bundle\DbalBundle\Exception\WriteDbalException;
use Elrise\Bundle\DbalBundle\Manager\Bulk\BulkUpserter;
use Elrise\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use Elrise\Bundle\DbalBundle\Sql\Builder\PostgresSqlBuilder;
use Elrise\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;
use Elrise\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(BulkUpserter::class)]
final class BulkUpserterTest extends TestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i';

    private Connection $connection;

    #[DataProvider('sqlBuildersProviderUpsertMany')]
    public function testUpsertReturnsRowCount(
        SqlBuilderInterface $builder,
        array $paramsList,
        array $replaceFields,
        string $expectedTable,
        string $expectedSql,
        array $expectedParams,
        array $expectedTypes,
        int $expectedAffected,
    ): void {
        $upserter = $this->createUpserter($builder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $expectedParams, $expectedTypes)
            ->willReturn($expectedAffected);

        $result = $upserter->upsertMany($expectedTable, $paramsList, $replaceFields);

        $this->assertSame($expectedAffected, $result);
    }

    #[DataProvider('sqlBuildersProviders')]
    public function testUpsertReturnsZeroOnEmptyInput(SqlBuilderInterface $builder): void
    {
        $upserter = $this->createUpserter($builder);

        $result = $upserter->upsertMany('users', [], []);

        $this->assertSame(0, $result);
    }

    #[DataProvider('sqlBuildersProviders')]
    public function testUpsertOneDelegatesToUpsertMany(SqlBuilderInterface $builder): void
    {
        $upserter = $this->createUpserter($builder);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->willReturn(1);

        $result = $upserter->upsertOne('users', ['id' => 1, 'name' => 'Alice'], ['name']);

        $this->assertSame(1, $result);
    }

    #[DataProvider('sqlBuildersProviders')]
    public function testUpsertManySplitsChunks(SqlBuilderInterface $builder): void
    {
        $upserter = $this->createUpserter($builder, 2);

        $paramsList = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Charlie'],
        ];

        $this->connection
            ->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnOnConsecutiveCalls(2, 1);

        $result = $upserter->upsertMany('users', $paramsList, ['name']);

        $this->assertSame(3, $result);
    }

    #[DataProvider('sqlBuildersProviders')]
    public function testUpsertHandlesDbalExceptions(SqlBuilderInterface $builder): void
    {
        $upserter = $this->createUpserter($builder);

        $this->expectException(WriteDbalException::class);

        $this->connection
            ->method('executeStatement')
            ->willThrowException(new DbalDriverException(new Exception('SQL failed'), null));

        $upserter->upsertMany('users', [['id' => 1, 'name' => 'Test']], ['name']);
    }

    public static function sqlBuildersProviderUpsertMany(): array
    {
        $now = date(self::DATE_FORMAT);

        return [
            [
                new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [
                    ['id' => 1, 'name' => 'Alex'],
                    ['id' => 2, 'name' => 'John'],
                ],
                ['name'],
                'users',
                'INSERT INTO `users` (`id`, `name`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?), (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `updated_at` = VALUES(`updated_at`)',
                [1, 'Alex', $now, $now, 2, 'John', $now, $now],
                [
                    ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING,
                ],
                42,
            ],
            [
                new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy()),
                [
                    ['id' => 1, 'name' => 'Alex'],
                    ['id' => 2, 'name' => 'John'],
                ],
                ['name'],
                'users',
                'INSERT INTO "users" ("id", "name", "created_at", "updated_at") VALUES (?, ?, ?, ?), (?, ?, ?, ?) ON CONFLICT ("name", "id") DO UPDATE SET "name" = EXCLUDED."name"',
                [1, 'Alex', $now, $now, 2, 'John', $now, $now],
                [
                    ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING,
                ],
                42,
            ],
        ];
    }

    public static function sqlBuildersProviders(): array
    {
        return [
            [new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy())],
            [new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy())],
        ];
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
    }

    private function createUpserter(SqlBuilderInterface $builder, int $chunkSize = 1000): BulkUpserter
    {
        return new BulkUpserter(
            $this->connection,
            $builder,
            new DbalBundleConfig(
                fieldNames: [
                    BundleConfigurationInterface::ID_NAME => 'id',
                    BundleConfigurationInterface::CREATED_AT_NAME => 'created_at',
                    BundleConfigurationInterface::UPDATED_AT_NAME => 'updated_at',
                ],
                chunkSize: $chunkSize,
                defaultDateTimeFormat: self::DATE_FORMAT,
            ),
        );
    }

    private function invokePrivateMethod(object $object, string $methodName, array $args = []): mixed
    {
        $ref = new ReflectionClass($object);
        $method = $ref->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
