<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Sql\Builder;

use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\TestCase;

final class MysqlSqlBuilderTest extends TestCase
{
    public function testGetInsertBulkSqlWithQuestionMarks(): void
    {
        $builder = new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy());

        $sql = $builder->getInsertBulkSql('users', [
            ['id' => 1, 'name' => 'Alex'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $expected = 'INSERT INTO `users` (id, name) VALUES (?, ?), (?, ?)';
        $this->assertEquals($expected, $sql);
    }

    public function testGetInsertBulkSqlThrowsOnEmptyParamsList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('paramsList must not be empty');

        $builder = new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy());
        $builder->getInsertBulkSql('users', []);
    }

    public function testGetUpdateBulkSqlWithQuestionMarks(): void
    {
        $builder = new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy());

        $paramsList = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $whereFields = ['id'];

        $expected = implode(' ', [
            'UPDATE `users` SET name = CASE',
            'WHEN (id = ?) THEN ?',
            'WHEN (id = ?) THEN ?',
            'ELSE name END',
            'WHERE (id = ?) OR (id = ?)',
        ]);

        $sql = $builder->getUpdateBulkSql('users', $paramsList, $whereFields);
        $this->assertEquals($expected, $sql);
    }

    public function testGetUpdateBulkSqlWithMultipleWhereFields(): void
    {
        $builder = new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy());

        $paramsList = [
            ['id' => 1, 'region_id' => 100, 'name' => 'Alice'],
            ['id' => 2, 'region_id' => 200, 'name' => 'Bob'],
        ];

        $whereFields = ['id', 'region_id'];

        $sql = $builder->getUpdateBulkSql('users', $paramsList, $whereFields);

        $this->assertStringContainsString('UPDATE `users` SET name = CASE', $sql);
        $this->assertStringContainsString('WHEN (id = ? AND region_id = ?) THEN ?', $sql);
        $this->assertStringContainsString('ELSE name END', $sql);
        $this->assertStringContainsString('WHERE (id = ? AND region_id = ?) OR (id = ? AND region_id = ?)', $sql);
    }
}
