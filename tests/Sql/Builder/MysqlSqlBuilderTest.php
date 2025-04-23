<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Sql\Builder;

use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use ITech\Bundle\DbalBundle\Sql\Builder\UpsertReplaceType;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\TestCase;

final class MysqlSqlBuilderTest extends TestCase
{
    private MysqlSqlBuilder $builder;

    public function testGetInsertBulkSqlWithQuestionMarks(): void
    {
        $sql = $this->builder->getInsertBulkSql('users', [
            ['id' => 1, 'name' => ['Alex', 'Kabanov']],
            ['id' => 2, 'name' => ['Bob', 'Marley']],
        ]);

        $expected = 'INSERT INTO `users` (id, name) VALUES (?, ?), (?, ?)';
        $this->assertEquals($expected, $sql);
    }

    public function testGetInsertBulkSqlThrowsOnEmptyParamsList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('paramsList must not be empty');

        $this->builder->getInsertBulkSql('users', []);
    }

    public function testGetUpdateBulkSqlWithQuestionMarks(): void
    {
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

        $sql = $this->builder->getUpdateBulkSql('users', $paramsList, $whereFields);
        $this->assertEquals($expected, $sql);
    }

    public function testGetUpdateBulkSqlWithQuestionMarksNoWhere(): void
    {
        $paramsList = [
            ['id' => 1, 'name' => 'Alice'],
        ];

        $whereFields = [];

        $this->expectException(InvalidArgumentException::class);

        $this->builder->getUpdateBulkSql('users', $paramsList, $whereFields);
    }

    public function testGetUpsertBulkSqlWithSimpleReplace(): void
    {
        $sql = $this->builder->getUpsertBulkSql('table_name', [
            ['a' => 1, 'b' => 2],
        ], ['a', 'b']);

        $expected = 'INSERT INTO `table_name` (a, b) VALUES (?, ?) ON DUPLICATE KEY UPDATE a = VALUES(a), b = VALUES(b)';
        $this->assertSame($expected, $sql);
    }

    public function testGetUpsertBulkSqlWithIncrement(): void
    {
        $sql = $this->builder->getUpsertBulkSql('table_name', [
            ['a' => 1, 'count' => 10],
        ], [
            ['count', UpsertReplaceType::Increment],
        ]);

        $expected = 'INSERT INTO `table_name` (a, count) VALUES (?, ?) ON DUPLICATE KEY UPDATE count = count + VALUES(count)';
        $this->assertSame($expected, $sql);
    }

    public function testGetUpsertBulkSqlWithDecrement(): void
    {
        $sql = $this->builder->getUpsertBulkSql('table_name', [
            ['a' => 1, 'count' => 10],
        ], [
            ['count', UpsertReplaceType::Decrement],
        ]);

        $expected = 'INSERT INTO `table_name` (a, count) VALUES (?, ?) ON DUPLICATE KEY UPDATE count = count - VALUES(count)';
        $this->assertSame($expected, $sql);
    }

    public function testGetUpsertBulkSqlWithCondition(): void
    {
        $sql = $this->builder->getUpsertBulkSql('table_name', [
            ['a' => 1, 'status' => 'active'],
        ], [
            ['status', UpsertReplaceType::Condition, 'IF(status != "archived", VALUES(status), status)'],
        ]);

        $expected = 'INSERT INTO `table_name` (a, status) VALUES (?, ?) ON DUPLICATE KEY UPDATE status = IF(status != "archived", VALUES(status), status)';
        $this->assertSame($expected, $sql);
    }

    public function testGetUpsertBulkSqlWithUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown UPSERT type: unknown');

        $this->builder->getUpsertBulkSql('table_name', [
            ['a' => 1, 'b' => 2],
        ], [
            ['b', 'unknown'],
        ]);
    }

    public function testGetDeleteBulkSqlWithSingleId(): void
    {
        $sql = $this->builder->getDeleteBulkSql('users', [1]);

        $expected = 'DELETE FROM `users` WHERE `id` IN (?)';
        $this->assertEquals($expected, $sql);
    }

    public function testGetDeleteBulkSqlWithMultipleIds(): void
    {
        $sql = $this->builder->getDeleteBulkSql('users', [1, 2, 3]);

        $expected = 'DELETE FROM `users` WHERE `id` IN (?, ?, ?)';
        $this->assertEquals($expected, $sql);
    }

    public function testGetDeleteBulkSqlWithEmptyIdList(): void
    {
        $sql = $this->builder->getDeleteBulkSql('users', []);

        $expected = 'DELETE FROM `users` WHERE `id` IN ()';
        $this->assertEquals($expected, $sql);
    }

    public function setUp(): void
    {
        $this->builder = new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy());
    }
}
