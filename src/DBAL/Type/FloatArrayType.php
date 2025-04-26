<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class FloatArrayType extends Type
{
    public const string NAME = 'float_array';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'FLOAT8[]';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        return json_decode(str_replace(['{', '}'], ['[', ']'], $value), true);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        return '{' . implode(',', array_map(
            static fn ($v) => number_format($v, 6, '.', ''),
            $value,
        )) . '}';
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
