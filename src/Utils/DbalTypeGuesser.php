<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Utils;

use Doctrine\DBAL\ParameterType;
use ITech\Bundle\DbalBundle\DBAL\DbalParameterType;
use PDO;

final class DbalTypeGuesser
{
    public static function guessParameterType(mixed $value): DbalParameterType
    {
        return match (true) {
            $value === null => DbalParameterType::NULL,
            is_int($value) => DbalParameterType::INTEGER,
            is_bool($value) => DbalParameterType::BOOLEAN,
            is_resource($value) => DbalParameterType::LARGE_OBJECT,
            is_array($value) => DbalParameterType::ARRAY,
            is_float($value) => DbalParameterType::FLOAT,
            default => DbalParameterType::STRING,
        };
    }

    public static function mapLegacyType(int $pdoType): DbalParameterType
    {
        return match ($pdoType) {
            PDO::PARAM_NULL => DbalParameterType::NULL,
            PDO::PARAM_INT => DbalParameterType::INTEGER,
            PDO::PARAM_BOOL => DbalParameterType::BOOLEAN,
            PDO::PARAM_LOB => DbalParameterType::LARGE_OBJECT,
            default => DbalParameterType::STRING,
        };
    }

    public static function toDoctrine(DbalParameterType|ParameterType|null $parameterType): ParameterType
    {
        if ($parameterType === null) {
            return ParameterType::NULL;
        }

        if ($parameterType instanceof ParameterType) {
            return $parameterType;
        }

        return match ($parameterType) {
            DbalParameterType::NULL => ParameterType::NULL,
            DbalParameterType::FLOAT,
            DbalParameterType::INTEGER => ParameterType::INTEGER,
            DbalParameterType::STRING,
            DbalParameterType::JSON,
            DbalParameterType::JSONB,
            DbalParameterType::UUID,
            DbalParameterType::TIMESTAMP,
            DbalParameterType::FLOAT_ARRAY,
            DbalParameterType::ARRAY => ParameterType::STRING,
            DbalParameterType::LARGE_OBJECT => ParameterType::LARGE_OBJECT,
            DbalParameterType::BOOLEAN => ParameterType::BOOLEAN,
            DbalParameterType::BINARY => ParameterType::BINARY,
            DbalParameterType::ASCII => ParameterType::ASCII,
        };
    }

    public static function fromDoctrine(ParameterType $parameterType): DbalParameterType
    {
        return match ($parameterType) {
            ParameterType::NULL => DbalParameterType::NULL,
            ParameterType::INTEGER => DbalParameterType::INTEGER,
            ParameterType::LARGE_OBJECT => DbalParameterType::LARGE_OBJECT,
            ParameterType::BOOLEAN => DbalParameterType::BOOLEAN,
            ParameterType::BINARY => DbalParameterType::BINARY,
            ParameterType::ASCII => DbalParameterType::ASCII,
            default => DbalParameterType::STRING,
        };
    }
}
