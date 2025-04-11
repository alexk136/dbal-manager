<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Sql\Placeholder;

use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PDO;
use PHPUnit\Framework\TestCase;

final class QuestionMarkPlaceholderStrategyTest extends TestCase
{
    public function testPrepareBulkParameterListsWithoutWhere(): void
    {
        $strategy = new QuestionMarkPlaceholderStrategy();

        $rows = [
            ['id' => 1, 'name' => ['Alex', PDO::PARAM_STR]],
            ['id' => 2, 'name' => ['Bob', PDO::PARAM_STR]],
        ];

        [$params, $types] = $strategy->prepareBulkParameterLists($rows);

        $this->assertEquals([1, 'Alex', 2, 'Bob'], $params);
        $this->assertEquals([null, PDO::PARAM_STR, null, PDO::PARAM_STR], $types);
    }

    public function testPrepareBulkParameterListsWithWhere(): void
    {
        $strategy = new QuestionMarkPlaceholderStrategy();

        $rows = [
            ['id' => [10, PDO::PARAM_INT], 'value' => [100, PDO::PARAM_INT]],
            ['id' => [20, PDO::PARAM_INT], 'value' => [200, PDO::PARAM_INT]],
        ];

        [$params, $types] = $strategy->prepareBulkParameterLists($rows, ['id']);

        $expectedParams = [
            10, 100,
            20, 200,
            10, 20,
        ];

        $expectedTypes = [
            PDO::PARAM_INT, PDO::PARAM_INT,
            PDO::PARAM_INT, PDO::PARAM_INT,
            PDO::PARAM_INT, PDO::PARAM_INT,
        ];

        $this->assertEquals($expectedParams, $params);
        $this->assertEquals($expectedTypes, $types);
    }

    public function testPrepareBulkParameterListsWithEmptyInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $strategy = new QuestionMarkPlaceholderStrategy();
        $strategy->prepareBulkParameterLists([]);
    }

    public function testMissingWhereFieldInRowIsIgnored(): void
    {
        $strategy = new QuestionMarkPlaceholderStrategy();

        $rows = [
            ['id' => [1, PDO::PARAM_INT], 'value' => [100, PDO::PARAM_INT]],
            ['value' => [200, PDO::PARAM_INT]],
        ];

        [$params] = $strategy->prepareBulkParameterLists($rows, ['id']);

        $this->assertContains(1, $params);
        $this->assertContains(100, $params);
        $this->assertContains(200, $params);
    }

    public function testMixedTypedAndUntypedValues(): void
    {
        $strategy = new QuestionMarkPlaceholderStrategy();

        $rows = [
            ['id' => [1, PDO::PARAM_INT], 'name' => 'Alice'],
            ['id' => 2, 'name' => ['Bob', PDO::PARAM_STR]],
        ];

        [$params, $types] = $strategy->prepareBulkParameterLists($rows);

        $this->assertEquals([1, 'Alice', 2, 'Bob'], $params);
        $this->assertEquals([PDO::PARAM_INT, null, null, PDO::PARAM_STR], $types);
    }
}
