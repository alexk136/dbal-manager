<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Tests\Sql\Builder\MySql;

use Elrise\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use Elrise\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use Elrise\Bundle\DbalBundle\Sql\Builder\UpsertReplaceType;
use Elrise\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(MysqlSqlBuilder::class)]
final class MysqlSqlBuilderUpsertTest extends TestCase
{
    private MysqlSqlBuilder $builder;

    #[DataProvider('provideUpsertBulkSqlData')]
    public function testGetUpsertBulkSql(string $tableName, array $paramsList, array $replaceFields, array $fieldNames, string $expectedSql): void
    {
        $sql = $this->builder->getUpsertBulkSql($tableName, $paramsList, $replaceFields, $fieldNames);
        $this->assertEquals($expectedSql, $sql);
    }

    public static function provideUpsertBulkSqlData(): array
    {
        return [
            'one row, simple replace' => [
                'users',
                [['id' => 1, 'name' => 'Alex']],
                ['name'],
                [],
                'INSERT INTO `users` (`id`, `name`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)',
            ],
            'two rows, simple replace' => [
                'users',
                [
                    ['id' => 1, 'name' => 'Alex'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                ['name'],
                [],
                'INSERT INTO `users` (`id`, `name`) VALUES (?, ?), (?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)',
            ],
            'one row, multiple replace fields' => [
                'users',
                [['id' => 1, 'name' => 'Alex', 'email' => 'alex@example.com']],
                ['name', 'email'],
                [],
                'INSERT INTO `users` (`id`, `name`, `email`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `email` = VALUES(`email`)',
            ],
            'one row, increment type' => [
                'users',
                [['id' => 1, 'counter' => 1]],
                [['counter', UpsertReplaceType::Increment]],
                [],
                'INSERT INTO `users` (`id`, `counter`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `counter` = `counter` + VALUES(`counter`)',
            ],
            'one row, decrement type' => [
                'users',
                [['id' => 1, 'counter' => 1]],
                [['counter', UpsertReplaceType::Decrement]],
                [],
                'INSERT INTO `users` (`id`, `counter`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `counter` = `counter` - VALUES(`counter`)',
            ],
            'one row, condition type' => [
                'users',
                [['id' => 1, 'status' => 'active']],
                [['status', UpsertReplaceType::Condition, "'inactive'"]],
                [],
                'INSERT INTO `users` (`id`, `status`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `status` = \'inactive\'',
            ],
            'one row, with fieldNames' => [
                'users',
                [['id' => 1, 'name' => 'Alex']],
                ['name'],
                [BundleConfigurationInterface::UPDATED_AT_NAME => 'updated_at'],
                'INSERT INTO `users` (`id`, `name`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `updated_at` = VALUES(`updated_at`)',
            ],
            'null values' => [
                'users',
                [['id' => 1, 'name' => null]],
                ['name'],
                [],
                'INSERT INTO `users` (`id`, `name`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)',
            ],
            'different table' => [
                'products',
                [['id' => 100, 'title' => 'Item']],
                ['title'],
                [],
                'INSERT INTO `products` (`id`, `title`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `title` = VALUES(`title`)',
            ],
        ];
    }

    public function testGetUpsertBulkSqlThrowsOnEmptyParamsList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('users', [], ['name']);
    }

    public function testGetUpsertBulkSqlThrowsOnInvalidTableName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('invalid#name', [['id' => 1, 'name' => 'Alex']], ['name']);
    }

    public function testGetUpsertBulkSqlThrowsOnInvalidParamsFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('users', [['id' => 1, 'invalid#field' => 'Alex']], ['name']);
    }

    public function testGetUpsertBulkSqlThrowsOnInvalidReplaceFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('users', [['id' => 1, 'name' => 'Alex']], ['invalid#field']);
    }

    public function testGetUpsertBulkSqlThrowsOnUnknownUpsertType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->getUpsertBulkSql('users', [['id' => 1, 'counter' => 1]], [['counter', 'invalid']]);
    }

    public function testGetUpsertBulkSqlUsesCache(): void
    {
        $tableName = 'users';
        $paramsList = [['id' => 1, 'name' => 'Alex']];
        $replaceFields = ['name'];
        $fieldNames = [];
        $sql1 = $this->builder->getUpsertBulkSql($tableName, $paramsList, $replaceFields, $fieldNames);
        $sql2 = $this->builder->getUpsertBulkSql($tableName, $paramsList, $replaceFields, $fieldNames);
        $this->assertSame($sql1, $sql2, 'SQL should be reused from cache');
    }

    public function testGetUpsertBulkSqlLimitsCacheSize(): void
    {
        $reflection = new ReflectionClass($this->builder);
        $sqlCache = $reflection->getProperty('sqlCache');
        $sqlCache->setAccessible(true);

        for ($i = 1; $i <= 1001; ++$i) {
            $this->builder->getUpsertBulkSql('table_' . $i, [['id' => $i, 'name' => 'Test']], ['name']);
        }

        $this->assertLessThanOrEqual(1000, count($sqlCache->getValue($this->builder)), 'Cache size should be limited to 1000');
    }

    protected function setUp(): void
    {
        $this->builder = new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy());
    }
}
