<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Sql\Placeholder;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use InvalidArgumentException;
use ITech\Bundle\DbalBundle\DBAL\DbalParameterType;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(QuestionMarkPlaceholderStrategy::class)]
final class QuestionMarkPlaceholderStrategyTest extends TestCase
{
    private QuestionMarkPlaceholderStrategy $strategy;

    #[DataProvider('platformProviderWithoutWhere')]
    public function testPrepareBulkParameterListsWithoutWhere(string $platformClass, array $rows, array $expectedSerializedArray): void
    {
        [$params, $types] = $this->strategy->prepareBulkParameterLists($rows, null, $platformClass);

        $this->assertEquals($params, $expectedSerializedArray[0]);
        $this->assertEquals($types, $expectedSerializedArray[1]);
    }

    #[DataProvider('platformProviderWithWhere')]
    public function testPrepareBulkParameterListsWithWhere(string $platformClass, array $rows, array $expectedParams, array $expectedTypes): void
    {
        [$params, $types] = $this->strategy->prepareBulkParameterLists($rows, ['id'], $platformClass);

        $this->assertEquals($expectedParams, $params);
        $this->assertEquals($expectedTypes, $types);
    }

    #[DataProvider('platformProviderWithWhere')]
    public function testPrepareBulkParameterListsWithEmptyInput(string $platformClass): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Batch rows must not be empty');

        $this->strategy->prepareBulkParameterLists([], null, $platformClass);
    }

    #[DataProvider('platformProviderMissingWhere')]
    public function testMissingWhereFieldInRowIsIgnored(string $platformClass, array $rows, array $expectedContained): void
    {
        [$params] = $this->strategy->prepareBulkParameterLists($rows, ['id'], $platformClass);

        foreach ($expectedContained as $expected) {
            $this->assertContains($expected, $params);
        }
    }

    #[DataProvider('platformProviderMixedTypedAndUntyped')]
    public function testMixedTypedAndUntypedValues(string $platformClass, array $rows, array $expectedParams, array $expectedTypes): void
    {
        [$params, $types] = $this->strategy->prepareBulkParameterLists($rows, null, $platformClass);

        $this->assertEquals($expectedParams, $params);
        $this->assertEquals($expectedTypes, $types);
    }

    #[DataProvider('platformProviderDefaultType')]
    public function testDefaultTyped(string $platformClass, array $rows, array $expectedParams, array $expectedTypes): void
    {
        [$params, $types] = $this->strategy->prepareBulkParameterLists($rows, null, $platformClass);

        $this->assertEquals($expectedParams, $params);
        $this->assertEquals($expectedTypes, $types);
    }

    public static function platformProviderWithoutWhere(): array
    {
        return [
            [
                PostgreSQLPlatform::class,
                [
                    ['id' => 1, 'name' => ['Alex', DbalParameterType::STRING]],
                    ['id' => 2, 'name' => ['Bob', DbalParameterType::STRING]],
                    ['id' => 3, 'name' => [['Alex', 'Bob'], DbalParameterType::ARRAY]],
                    ['id' => 4, 'name' => [[1.23, 4.56], DbalParameterType::FLOAT_ARRAY]],
                ],
                [
                    [1, 'Alex', 2, 'Bob', 3, '{"Alex","Bob"}', 4, '{1.230000,4.560000}'],
                    [
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                    ],
                ],
            ],
            [
                MySQLPlatform::class,
                [
                    ['id' => 1, 'name' => ['Alex', DbalParameterType::STRING]],
                    ['id' => 2, 'name' => ['Bob', DbalParameterType::STRING]],
                    ['id' => 3, 'name' => [['Alex', 'Bob'], DbalParameterType::ARRAY]],
                ],
                [
                    [1, 'Alex', 2, 'Bob', 3, '["Alex","Bob"]'],
                    [
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                    ],
                ],
            ],
        ];
    }

    public static function platformProviderWithWhere(): array
    {
        return [
            [
                PostgreSQLPlatform::class,
                [
                    ['id' => [10, ParameterType::INTEGER], 'value' => [100, ParameterType::INTEGER]],
                    ['id' => [20, ParameterType::INTEGER], 'value' => [200, ParameterType::INTEGER]],
                ],
                [
                    10, 100,
                    20, 200,
                    10, 20,
                ],
                [
                    ParameterType::INTEGER, ParameterType::INTEGER,
                    ParameterType::INTEGER, ParameterType::INTEGER,
                    ParameterType::INTEGER, ParameterType::INTEGER,
                ],
            ],
            [
                MySQLPlatform::class,
                [
                    ['id' => [10, ParameterType::INTEGER], 'value' => [100, ParameterType::INTEGER]],
                    ['id' => [20, ParameterType::INTEGER], 'value' => [200, ParameterType::INTEGER]],
                ],
                [
                    10, 100,
                    20, 200,
                    10, 20,
                ],
                [
                    ParameterType::INTEGER, ParameterType::INTEGER,
                    ParameterType::INTEGER, ParameterType::INTEGER,
                    ParameterType::INTEGER, ParameterType::INTEGER,
                ],
            ],
        ];
    }

    public static function platformProviderMissingWhere(): array
    {
        return [
            [
                PostgreSQLPlatform::class,
                [
                    ['id' => 1, 'value' => 100],
                    ['value' => 200],
                ],
                [1, 100, 200],
            ],
            [
                MySQLPlatform::class,
                [
                    ['id' => 1, 'value' => 100],
                    ['value' => 200],
                ],
                [1, 100, 200],
            ],
        ];
    }

    public static function platformProviderMixedTypedAndUntyped(): array
    {
        return [
            [
                PostgreSQLPlatform::class,
                [
                    ['id' => [1, PDO::PARAM_INT], 'name' => 'Alice'],
                    ['id' => 2, 'name' => ['Bob', PDO::PARAM_STR]],
                ],
                [1, 'Alice', 2, 'Bob'],
                [
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                ],
            ],
            [
                MySQLPlatform::class,
                [
                    ['id' => [1, PDO::PARAM_INT], 'name' => 'Alice'],
                    ['id' => 2, 'name' => ['Bob', PDO::PARAM_STR]],
                ],
                [1, 'Alice', 2, 'Bob'],
                [
                    ParameterType::INTEGER, ParameterType::STRING,
                    ParameterType::INTEGER, ParameterType::STRING,
                ],
            ],
        ];
    }

    public static function platformProviderDefaultType(): array
    {
        return [
            'postgres: dbal default set' => [
                PostgreSQLPlatform::class,
                [
                    ['id' => ['DEFAULT', DbalParameterType::DEFAULT], 'name' => 'Alice'],
                ],
                ['Alice'],
                [
                    ParameterType::STRING,
                ],
            ],
            'postgres: dbal default null' => [
                PostgreSQLPlatform::class,
                [
                    ['id' => [null, DbalParameterType::DEFAULT], 'name' => 'Bob'],
                ],
                ['Bob'],
                [
                    ParameterType::STRING,
                ],
            ],
            'postgres: dbal default ANY and default set' => [
                PostgreSQLPlatform::class,
                [
                    ['id' => ['ANY', DbalParameterType::DEFAULT], 'name' => 'Alex'],
                ],
                ['Alex'],
                [
                    ParameterType::STRING,
                ],
            ],
            'postgres: dbal default string only' => [
                PostgreSQLPlatform::class,
                [
                    ['id' => ['DEFAULT'], 'name' => 'Vicky'],
                ],
                ['DEFAULT', 'Vicky'],
                [
                    ParameterType::STRING,
                    ParameterType::STRING,
                ],
            ],
            'mysql: dbal default set' => [
                MySQLPlatform::class,
                [
                    ['id' => ['DEFAULT', DbalParameterType::DEFAULT], 'name' => 'Alice'],
                ],
                ['Alice'],
                [
                    ParameterType::STRING,
                ],
            ],
            'mysql: dbal default null' => [
                MySQLPlatform::class,
                [
                    ['id' => [null, DbalParameterType::DEFAULT], 'name' => 'Bob'],
                ],
                ['Bob'],
                [
                    ParameterType::STRING,
                ],
            ],
            'mysql: dbal default ANY and default set' => [
                MySQLPlatform::class,
                [
                    ['id' => ['ANY', DbalParameterType::DEFAULT], 'name' => 'Alex'],
                ],
                ['Alex'],
                [
                    ParameterType::STRING,
                ],
            ],
            'mysql: dbal default string only' => [
                MySQLPlatform::class,
                [
                    ['id' => ['DEFAULT'], 'name' => 'Vicky'],
                ],
                ['DEFAULT', 'Vicky'],
                [
                    ParameterType::STRING,
                    ParameterType::STRING,
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->strategy = new QuestionMarkPlaceholderStrategy();
    }
}
