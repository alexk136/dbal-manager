<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Utils;

use Doctrine\DBAL\ParameterType;
use PDO;

final class DbalTypeGuesser
{
    public static function guessParameterType(mixed $value): ParameterType
    {
        return match (true) {
            $value === null => ParameterType::NULL,
            is_int($value) => ParameterType::INTEGER,
            is_bool($value) => ParameterType::BOOLEAN,
            default => ParameterType::STRING,
        };
    }

    public static function mapLegacyType(int $pdoType): ParameterType
    {
        return match ($pdoType) {
            PDO::PARAM_NULL => ParameterType::NULL,
            PDO::PARAM_INT => ParameterType::INTEGER,
            PDO::PARAM_BOOL => ParameterType::BOOLEAN,
            default => ParameterType::STRING,
        };
    }
}
