<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Sql\Placeholder;

use Doctrine\DBAL\ParameterType;
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
            ['id' => 1, 'name' => ['Alex', ParameterType::STRING]],
            ['id' => 2, 'name' => ['Bob', ParameterType::STRING]],
        ];

        [$params, $types] = $strategy->prepareBulkParameterLists($rows);

        $this->assertEquals([1, 'Alex', 2, 'Bob'], $params);
        $this->assertEquals([ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING], $types);
    }

    public function testPrepareBulkParameterListsWithWhere(): void
    {
        $strategy = new QuestionMarkPlaceholderStrategy();

        $rows = [
            ['id' => [10, ParameterType::INTEGER], 'value' => [100, ParameterType::INTEGER]],
            ['id' => [20, ParameterType::INTEGER], 'value' => [200, ParameterType::INTEGER]],
        ];

        [$params, $types] = $strategy->prepareBulkParameterLists($rows, ['id']);

        $expectedParams = [
            10, 100,
            20, 200,
            10, 20,
        ];

        $expectedTypes = [
            ParameterType::INTEGER, ParameterType::INTEGER,
            ParameterType::INTEGER, ParameterType::INTEGER,
            ParameterType::INTEGER, ParameterType::INTEGER,
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
            ['id' => 1, 'value' => 100],
            ['value' => 200],
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
        $this->assertEquals([ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING], $types);
    }
}
