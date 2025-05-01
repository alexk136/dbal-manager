<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class Float4ArrayType extends Type
{
    public const string NAME = 'float4_array';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'FLOAT4[]'; // отличается только тут
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
            static fn ($v) => is_float($v) ? number_format($v, 6, '.', '') : (string) $v,
            $value,
        )) . '}';
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
