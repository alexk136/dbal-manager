<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Util;

use Doctrine\DBAL\ParameterType;

final class DbalTypeGuesser
{
    public static function guessParameterType(mixed $value): ParameterType
    {
        return match (true) {
            is_int($value) => ParameterType::INTEGER,
            is_bool($value) => ParameterType::BOOLEAN,
            default => ParameterType::STRING,
        };
    }
}
