<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Tests\Utils;

use Doctrine\DBAL\ParameterType;
use Elrise\Bundle\DbalBundle\DBAL\DbalParameterType;
use Elrise\Bundle\DbalBundle\Utils\DbalTypeGuesser;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DbalTypeGuesser::class)]
final class DbalTypeGuesserTest extends TestCase
{
    #[DataProvider('guessParameterTypeProvider')]
    public function testGuessParameterType(mixed $input, DbalParameterType $expected): void
    {
        $this->assertSame($expected, DbalTypeGuesser::guessParameterType($input));
    }

    public static function guessParameterTypeProvider(): array
    {
        return [
            'null' => [null, DbalParameterType::NULL],
            'integer' => [123, DbalParameterType::INTEGER],
            'boolean' => [true, DbalParameterType::BOOLEAN],
            'resource' => [fopen('php://memory', 'r'), DbalParameterType::LARGE_OBJECT],
            'array' => [[1, 2, 3], DbalParameterType::ARRAY],
            'string' => ['text', DbalParameterType::STRING],
            'float' => [1.23, DbalParameterType::FLOAT],
        ];
    }

    #[DataProvider('mapLegacyTypeProvider')]
    public function testMapLegacyType(int $pdoType, DbalParameterType $expected): void
    {
        $this->assertSame($expected, DbalTypeGuesser::mapLegacyType($pdoType));
    }

    public static function mapLegacyTypeProvider(): array
    {
        return [
            'PDO::PARAM_NULL' => [PDO::PARAM_NULL, DbalParameterType::NULL],
            'PDO::PARAM_INT' => [PDO::PARAM_INT, DbalParameterType::INTEGER],
            'PDO::PARAM_BOOL' => [PDO::PARAM_BOOL, DbalParameterType::BOOLEAN],
            'PDO::PARAM_LOB' => [PDO::PARAM_LOB, DbalParameterType::LARGE_OBJECT],
            'PDO::PARAM_STR (default)' => [PDO::PARAM_STR, DbalParameterType::STRING],
        ];
    }

    #[DataProvider('toDoctrineProvider')]
    public function testToDoctrine(DbalParameterType|ParameterType|null $input, ParameterType $expected): void
    {
        $this->assertSame($expected, DbalTypeGuesser::toDoctrine($input));
    }

    public static function toDoctrineProvider(): array
    {
        return [
            'null' => [null, ParameterType::NULL],
            'Dbal NULL' => [DbalParameterType::NULL, ParameterType::NULL],
            'Dbal INTEGER' => [DbalParameterType::INTEGER, ParameterType::INTEGER],
            'Dbal STRING' => [DbalParameterType::STRING, ParameterType::STRING],
            'Dbal JSON' => [DbalParameterType::JSON, ParameterType::STRING],
            'Dbal ARRAY' => [DbalParameterType::ARRAY, ParameterType::STRING],
            'Dbal FLOAT_ARRAY' => [DbalParameterType::FLOAT_ARRAY, ParameterType::STRING],
            'Dbal UUID' => [DbalParameterType::UUID, ParameterType::STRING],
            'Dbal TIMESTAMP' => [DbalParameterType::TIMESTAMP, ParameterType::STRING],
            'Dbal LARGE_OBJECT' => [DbalParameterType::LARGE_OBJECT, ParameterType::LARGE_OBJECT],
            'Dbal BOOLEAN' => [DbalParameterType::BOOLEAN, ParameterType::BOOLEAN],
            'Dbal BINARY' => [DbalParameterType::BINARY, ParameterType::BINARY],
            'Dbal ASCII' => [DbalParameterType::ASCII, ParameterType::ASCII],
            'Already ParameterType' => [ParameterType::STRING, ParameterType::STRING],
        ];
    }

    #[DataProvider('fromDoctrineProvider')]
    public function testFromDoctrine(ParameterType $input, DbalParameterType $expected): void
    {
        $this->assertSame($expected, DbalTypeGuesser::fromDoctrine($input));
    }

    public static function fromDoctrineProvider(): array
    {
        return [
            'Doctrine NULL' => [ParameterType::NULL, DbalParameterType::NULL],
            'Doctrine INTEGER' => [ParameterType::INTEGER, DbalParameterType::INTEGER],
            'Doctrine LARGE_OBJECT' => [ParameterType::LARGE_OBJECT, DbalParameterType::LARGE_OBJECT],
            'Doctrine BOOLEAN' => [ParameterType::BOOLEAN, DbalParameterType::BOOLEAN],
            'Doctrine BINARY' => [ParameterType::BINARY, DbalParameterType::BINARY],
            'Doctrine ASCII' => [ParameterType::ASCII, DbalParameterType::ASCII],
            'Doctrine STRING (default)' => [ParameterType::STRING, DbalParameterType::STRING],
        ];
    }
}
