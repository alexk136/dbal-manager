<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Sql\Builder\MySql;

use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(MysqlSqlBuilder::class)]
final class MysqlSqlBuilderInsertTest extends TestCase
{
    private MysqlSqlBuilder $builder;

    #[DataProvider('provideInsertBulkSqlData')]
    public function testGetInsertBulkSql(string $tableName, array $paramsList, bool $isIgnore, string $expectedSql): void
    {
        $sql = $this->builder->getInsertBulkSql($tableName, $paramsList, $isIgnore);
        $this->assertEquals($expectedSql, $sql);
    }

    public static function provideInsertBulkSqlData(): array
    {
        return [
            'single row, two fields' => [
                'users',
                [['id' => 1, 'name' => 'Alex']],
                false,
                'INSERT INTO `users` (`id`, `name`) VALUES (?, ?)',
            ],
            'multiple rows, two fields' => [
                'users',
                [
                    ['id' => 1, 'name' => 'Alex'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                false,
                'INSERT INTO `users` (`id`, `name`) VALUES (?, ?), (?, ?)',
            ],
            'single row, isIgnore' => [
                'users',
                [['id' => 1, 'name' => 'Alex']],
                true,
                'INSERT IGNORE INTO `users` (`id`, `name`) VALUES (?, ?)',
            ],
            'single row, single field' => [
                'users',
                [['id' => 1]],
                false,
                'INSERT INTO `users` (`id`) VALUES (?)',
            ],
            'null values' => [
                'users',
                [['id' => 1, 'name' => null]],
                false,
                'INSERT INTO `users` (`id`, `name`) VALUES (?, ?)',
            ],
            'different table' => [
                'products',
                [['id' => 100, 'name' => 'Item']],
                false,
                'INSERT INTO `products` (`id`, `name`) VALUES (?, ?)',
            ],
            'large dataset' => [
                'users',
                array_fill(0, 10, ['id' => 1, 'name' => 'Test']),
                false,
                'INSERT INTO `users` (`id`, `name`) VALUES ' . implode(', ', array_fill(0, 10, '(?, ?)')),
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
        $this->builder->getInsertBulkSql('invalid#name', [['id' => 1, 'name' => 'Alex']]);
    }

    public function testGetInsertBulkSqlThrowsOnInvalidFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getInsertBulkSql('users', [['id' => 1, 'invalid#field' => 'Alex']]);
    }

    public function testGetInsertBulkSqlUsesCache(): void
    {
        $tableName = 'users';
        $paramsList = [['id' => 1, 'name' => 'Alex'], ['id' => 2, 'name' => 'Bob']];
        $sql1 = $this->builder->getInsertBulkSql($tableName, $paramsList);
        $sql2 = $this->builder->getInsertBulkSql($tableName, $paramsList);
        $this->assertSame($sql1, $sql2, 'SQL should be reused from cache');
    }

    public function testGetInsertBulkSqlLimitsCacheSize(): void
    {
        $reflection = new ReflectionClass($this->builder);
        $sqlCache = $reflection->getProperty('sqlCache');
        $sqlCache->setAccessible(true);

        for ($i = 1; $i <= 1001; ++$i) {
            $this->builder->getInsertBulkSql('table_' . $i, [['id' => $i, 'name' => 'Test']]);
        }

        $this->assertLessThanOrEqual(1000, count($sqlCache->getValue($this->builder)), 'Cache size should be limited to 1000');
    }

    protected function setUp(): void
    {
        $this->builder = new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy());
    }
}
