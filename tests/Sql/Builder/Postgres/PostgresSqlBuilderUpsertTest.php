<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Sql\Builder\Postgres;

use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Sql\Builder\PostgresSqlBuilder;
use ITech\Bundle\DbalBundle\Sql\Builder\UpsertReplaceType;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PostgresSqlBuilder::class)]
final class PostgresSqlBuilderUpsertTest extends TestCase
{
    private PostgresSqlBuilder $builder;

    #[DataProvider('provideUpsertBulkSqlData')]
    public function testGetUpsertBulkSql(array $paramsList, array $replaceFields, array $fieldNames, string $expectedSql): void
    {
        $sql = $this->builder->getUpsertBulkSql('users', $paramsList, $replaceFields, $fieldNames);
        $this->assertEquals($expectedSql, $sql);
    }

    public static function provideUpsertBulkSqlData(): array
    {
        return [
            'one row, simple replace' => [
                [['id' => 1, 'name' => 'Alex']],
                ['name'],
                [],
                'INSERT INTO "users" ("id", "name") VALUES (?, ?) ON CONFLICT ("name") DO UPDATE SET "name" = EXCLUDED."name"',
            ],
            'two rows, simple replace' => [
                [
                    ['id' => 1, 'name' => 'Alex'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                ['name'],
                [],
                'INSERT INTO "users" ("id", "name") VALUES (?, ?), (?, ?) ON CONFLICT ("name") DO UPDATE SET "name" = EXCLUDED."name"',
            ],
            'one row, multiple replace fields' => [
                [['id' => 1, 'name' => 'Alex', 'email' => 'alex@example.com']],
                ['name', 'email'],
                [],
                'INSERT INTO "users" ("id", "name", "email") VALUES (?, ?, ?) ON CONFLICT ("name", "email") DO UPDATE SET "name" = EXCLUDED."name", "email" = EXCLUDED."email"',
            ],
            'one row, increment type' => [
                [['id' => 1, 'counter' => 1]],
                [['counter', UpsertReplaceType::Increment]],
                [],
                'INSERT INTO "users" ("id", "counter") VALUES (?, ?) ON CONFLICT ("counter") DO UPDATE SET "counter" = "counter" + EXCLUDED."counter"',
            ],
            'one row, decrement type' => [
                [['id' => 1, 'counter' => 1]],
                [['counter', UpsertReplaceType::Decrement]],
                [],
                'INSERT INTO "users" ("id", "counter") VALUES (?, ?) ON CONFLICT ("counter") DO UPDATE SET "counter" = "counter" - EXCLUDED."counter"',
            ],
            'one row, condition type' => [
                [['id' => 1, 'status' => 'active']],
                [['status', UpsertReplaceType::Condition, "'inactive'"]],
                [],
                'INSERT INTO "users" ("id", "status") VALUES (?, ?) ON CONFLICT ("status") DO UPDATE SET "status" = \'inactive\'',
            ],
            'one row, with fieldNames' => [
                [['id' => 1, 'name' => 'Alex']],
                ['name'],
                [BundleConfigurationInterface::ID_NAME => 'id'],
                'INSERT INTO "users" ("id", "name") VALUES (?, ?) ON CONFLICT ("name", "id") DO UPDATE SET "name" = EXCLUDED."name"',
            ],
            'null values' => [
                [['id' => 1, 'name' => null]],
                ['name'],
                [],
                'INSERT INTO "users" ("id", "name") VALUES (?, ?) ON CONFLICT ("name") DO UPDATE SET "name" = EXCLUDED."name"',
            ],
        ];
    }

    public function testGetUpsertBulkSqlThrowsOnEmptyParamsList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('users', [], ['name']);
    }

    public function testGetUpsertBulkSqlThrowsOnEmptyReplaceFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('users', [['id' => 1, 'name' => 'Alex']], []);
    }

    public function testGetUpsertBulkSqlThrowsOnInvalidTableName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('invalid#name', [['id' => 1, 'name' => 'Alex']], ['name']);
    }

    public function testGetUpsertBulkSqlThrowsOnInvalidConflictFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('users', [['id' => 1, 'name' => 'Alex']], ['invalid#name']);
    }

    public function testGetUpsertBulkSqlThrowsOnUnknownUpsertType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('users', [['id' => 1, 'counter' => 1]], [['counter', 'invalid']]);
    }

    public function testGetUpsertBulkSqlUsesCache(): void
    {
        $paramsList = [['id' => 1, 'name' => 'Alex']];
        $replaceFields = ['name'];
        $fieldNames = [];
        $sql1 = $this->builder->getUpsertBulkSql('users', $paramsList, $replaceFields, $fieldNames);
        $sql2 = $this->builder->getUpsertBulkSql('users', $paramsList, $replaceFields, $fieldNames);
        $this->assertSame($sql1, $sql2, 'SQL should be reused from cache');
    }

    public function testGetUpsertBulkSqlLimitsCacheSize(): void
    {
        $reflection = new ReflectionClass($this->builder);
        $sqlCache = $reflection->getProperty('sqlCache');
        $sqlCache->setAccessible(true);

        for ($i = 0; $i < 1001; ++$i) {
            $this->builder->getUpsertBulkSql('users', [['id' => $i, 'name' => 'Test']], ['name']);
        }

        $this->assertLessThanOrEqual(1000, count($sqlCache->getValue($this->builder)), 'Cache size should be limited to 1000');
    }

    protected function setUp(): void
    {
        $this->builder = new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy());
    }
}
