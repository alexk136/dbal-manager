<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Tests\Sql\Builder\MySql;

use Elrise\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use Elrise\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(MysqlSqlBuilder::class)]
final class MysqlSqlBuilderUpdateTest extends TestCase
{
    private MysqlSqlBuilder $builder;

    #[DataProvider('provideUpdateBulkSqlData')]
    public function testGetUpdateBulkSql(string $tableName, array $paramsList, array $whereFields, string $expectedSql): void
    {
        $sql = $this->builder->getUpdateBulkSql($tableName, $paramsList, $whereFields);
        $this->assertEquals($expectedSql, $sql);
    }

    public static function provideUpdateBulkSqlData(): array
    {
        return [
            'one row, one update field, one where field' => [
                'users',
                [['id' => 1, 'name' => 'Alex']],
                ['id'],
                'UPDATE `users` SET `name` = CASE WHEN (`id` = ?) THEN ? ELSE `name` END WHERE (`id` = ?)',
            ],
            'two rows, one update field, one where field' => [
                'users',
                [
                    ['id' => 1, 'name' => 'Alex'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                ['id'],
                'UPDATE `users` SET `name` = CASE WHEN (`id` = ?) THEN ? WHEN (`id` = ?) THEN ? ELSE `name` END WHERE (`id` = ?) OR (`id` = ?)',
            ],
            'one row, two update fields, one where field' => [
                'users',
                [['id' => 1, 'name' => 'Alex', 'email' => 'alex@example.com']],
                ['id'],
                'UPDATE `users` SET `name` = CASE WHEN (`id` = ?) THEN ? ELSE `name` END, `email` = CASE WHEN (`id` = ?) THEN ? ELSE `email` END WHERE (`id` = ?)',
            ],
            'one row, one update field, two where fields' => [
                'users',
                [['id' => 1, 'code' => 'A', 'name' => 'Alex']],
                ['id', 'code'],
                'UPDATE `users` SET `name` = CASE WHEN (`id` = ? AND `code` = ?) THEN ? ELSE `name` END WHERE (`id` = ? AND `code` = ?)',
            ],
            'null values' => [
                'users',
                [['id' => 1, 'name' => null]],
                ['id'],
                'UPDATE `users` SET `name` = CASE WHEN (`id` = ?) THEN ? ELSE `name` END WHERE (`id` = ?)',
            ],
            'different table' => [
                'products',
                [['id' => 100, 'title' => 'Item']],
                ['id'],
                'UPDATE `products` SET `title` = CASE WHEN (`id` = ?) THEN ? ELSE `title` END WHERE (`id` = ?)',
            ],
        ];
    }

    public function testGetUpdateBulkSqlThrowsOnEmptyParamsList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpdateBulkSql('users', [], ['id']);
    }

    public function testGetUpdateBulkSqlThrowsOnEmptyWhereFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpdateBulkSql('users', [['id' => 1, 'name' => 'Alex']], []);
    }

    public function testGetUpdateBulkSqlThrowsOnInvalidTableName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpdateBulkSql('invalid#name', [['id' => 1, 'name' => 'Alex']], ['id']);
    }

    public function testGetUpdateBulkSqlThrowsOnInvalidWhereFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpdateBulkSql('users', [['id' => 1, 'name' => 'Alex']], ['invalid#field']);
    }

    public function testGetUpdateBulkSqlThrowsOnInvalidParamsFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpdateBulkSql('users', [['id' => 1, 'invalid#field' => 'Alex']], ['id']);
    }

    public function testGetUpdateBulkSqlUsesCache(): void
    {
        $tableName = 'users';
        $paramsList = [['id' => 1, 'name' => 'Alex']];
        $whereFields = ['id'];
        $sql1 = $this->builder->getUpdateBulkSql($tableName, $paramsList, $whereFields);
        $sql2 = $this->builder->getUpdateBulkSql($tableName, $paramsList, $whereFields);
        $this->assertSame($sql1, $sql2, 'SQL should be reused from cache');
    }

    public function testGetUpdateBulkSqlLimitsCacheSize(): void
    {
        $reflection = new ReflectionClass($this->builder);
        $sqlCache = $reflection->getProperty('sqlCache');
        $sqlCache->setAccessible(true);

        for ($i = 1; $i <= 1001; ++$i) {
            $this->builder->getUpdateBulkSql('table_' . $i, [['id' => $i, 'name' => 'Test']], ['id']);
        }

        $this->assertLessThanOrEqual(1000, count($sqlCache->getValue($this->builder)), 'Cache size should be limited to 1000');
    }

    protected function setUp(): void
    {
        $this->builder = new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy());
    }
}
