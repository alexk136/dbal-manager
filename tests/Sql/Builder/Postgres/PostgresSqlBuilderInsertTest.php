<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Sql\Builder\Postgres;

use InvalidArgumentException;
use ITech\Bundle\DbalBundle\DBAL\DbalParameterType;
use ITech\Bundle\DbalBundle\Sql\Builder\PostgresSqlBuilder;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PostgresSqlBuilder::class)]
final class PostgresSqlBuilderInsertTest extends TestCase
{
    private PostgresSqlBuilder $builder;

    #[DataProvider('provideInsertBulkSqlData')]
    public function testGetInsertBulkSql(array $paramsList, bool $isIgnore, string $expectedSql): void
    {
        $sql = $this->builder->getInsertBulkSql('users', $paramsList, $isIgnore);
        $this->assertEquals($expectedSql, $sql);
    }

    public static function provideInsertBulkSqlData(): array
    {
        return [
            'one row' => [
                [['id' => 1, 'name' => 'Alex']],
                false,
                'INSERT INTO "users" ("id", "name") VALUES (?, ?)',
            ],
            'two rows' => [
                [
                    ['id' => 1, 'name' => 'Alex'],
                    ['id' => 2, 'name' => 'Bobte'],
                ],
                false,
                'INSERT INTO "users" ("id", "name") VALUES (?, ?), (?, ?)',
            ],
            'ten rows' => [
                array_fill(0, 10, ['id' => 1, 'name' => 'Test']),
                false,
                'INSERT INTO "users" ("id", "name") VALUES ' . implode(', ', array_fill(0, 10, '(?, ?)')),
            ],
            'one row with isIgnore' => [
                [['id' => 1, 'name' => 'Alex']],
                true,
                'INSERT INTO "users" ("id", "name") VALUES (?, ?) ON CONFLICT DO NOTHING',
            ],
            'single field' => [
                [['id' => 1]],
                false,
                'INSERT INTO "users" ("id") VALUES (?)',
            ],
            'null values' => [
                [['id' => 1, 'name' => null]],
                false,
                'INSERT INTO "users" ("id", "name") VALUES (?, ?)',
            ],
            'one row with default id' => [
                [['id' => ['DEFAULT', DbalParameterType::DEFAULT], 'name' => 'Alex']],
                false,
                'INSERT INTO "users" ("id", "name") VALUES (DEFAULT, ?)',
            ],
            'two rows with default id' => [
                [
                    ['id' => ['DEFAULT', DbalParameterType::DEFAULT], 'name' => 'Alex'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                false,
                'INSERT INTO "users" ("id", "name") VALUES (DEFAULT, ?), (?, ?)',
            ],
            'one row with default id and isIgnore' => [
                [['id' => [null, DbalParameterType::DEFAULT], 'name' => 'Alex']],
                true,
                'INSERT INTO "users" ("id", "name") VALUES (DEFAULT, ?) ON CONFLICT DO NOTHING',
            ],
        ];
    }

    public function testGetInsertBulkSqlThrowsOnEmptyParamsList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getInsertBulkSql('users', []);
    }

    public function testGetInsertBulkSqlThrowsOnInvalidTableName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getInsertBulkSql('invalid#name', [['id' => 1]]);
    }

    public function testGetInsertBulkSqlThrowsOnInvalidFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getInsertBulkSql('users', [['invalid#name' => 1]]);
    }

    public function testGetInsertBulkSqlUsesCache(): void
    {
        $paramsList = [['id' => 1, 'name' => 'Alex']];
        $sql1 = $this->builder->getInsertBulkSql('users', $paramsList);
        $sql2 = $this->builder->getInsertBulkSql('users', $paramsList);
        $this->assertSame($sql1, $sql2, 'SQL should be reused from cache');
    }

    public function testGetInsertBulkSqlLimitsCacheSize(): void
    {
        $reflection = new ReflectionClass($this->builder);
        $sqlCache = $reflection->getProperty('sqlCache');
        $sqlCache->setAccessible(true);

        for ($i = 0; $i < 1001; ++$i) {
            $this->builder->getInsertBulkSql('users', [['id' => $i, 'name' => 'Test']]);
        }

        $this->assertLessThanOrEqual(1000, count($sqlCache->getValue($this->builder)), 'Cache size should be limited to 1000');
    }

    protected function setUp(): void
    {
        $this->builder = new PostgresSqlBuilder(new QuestionMarkPlaceholderStrategy());
    }
}
