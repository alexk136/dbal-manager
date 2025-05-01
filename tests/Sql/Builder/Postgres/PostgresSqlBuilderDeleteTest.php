<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Sql\Builder\Postgres;

use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Sql\Builder\PostgresSqlBuilder;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PostgresSqlBuilder::class)]
final class PostgresSqlBuilderDeleteTest extends TestCase
{
    private PostgresSqlBuilder $builder;

    #[DataProvider('provideDeleteBulkSqlData')]
    public function testGetDeleteBulkSql(string $tableName, array $idList, string $expectedSql): void
    {
        $sql = $this->builder->getDeleteBulkSql($tableName, $idList);
        $this->assertEquals($expectedSql, $sql);
    }

    public static function provideDeleteBulkSqlData(): array
    {
        return [
            'single id' => [
                'users',
                [1],
                'DELETE FROM "users" WHERE "id" IN (?)',
            ],
            'multiple ids' => [
                'users',
                [1, 2, 3],
                'DELETE FROM "users" WHERE "id" IN (?, ?, ?)',
            ],
            'different table' => [
                'products',
                [100, 200],
                'DELETE FROM "products" WHERE "id" IN (?, ?)',
            ],
            'large id list' => [
                'users',
                range(1, 10),
                'DELETE FROM "users" WHERE "id" IN (' . implode(', ', array_fill(0, 10, '?')) . ')',
            ],
        ];
    }

    public function testGetDeleteBulkSqlThrowsOnEmptyIdList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getDeleteBulkSql('users', []);
    }

    public function testGetDeleteBulkSqlThrowsOnInvalidTableName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getDeleteBulkSql('invalid#name', [1]);
    }

    public function testGetDeleteBulkSqlUsesCache(): void
    {
        $tableName = 'users';
        $idList = [1, 2];
        $sql1 = $this->builder->getDeleteBulkSql($tableName, $idList);
        $sql2 = $this->builder->getDeleteBulkSql($tableName, $idList);
        $this->assertSame($sql1, $sql2, 'SQL should be reused from cache');
    }

    public function testGetDeleteBulkSqlLimitsCacheSize(): void
    {
        $reflection = new ReflectionClass($this->builder);
        $sqlCache = $reflection->getProperty('sqlCache');
        $sqlCache->setAccessible(true);

        for ($i = 1; $i <= 1001; ++$i) {
            $this->builder->getDeleteBulkSql('table_' . $i, [1]);
        }

        $this->assertLessThanOrEqual(1000, count($sqlCache->getValue($this->builder)), 'Cache size should be limited to 1000');
    }

    protected function setUp(): void
    {
        $this->builder = new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy());
    }
}
