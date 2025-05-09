<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Utils;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use JsonException;

final class ArraySerializer
{
    /**
     * Serializes an array for a specific platform.
     *
     * @throws JsonException
     */
    public static function serialize(array $value, ?string $platform = null): string
    {
        if ($platform === PostgreSQLPlatform::class) {
            return '{' . implode(',', array_map(
                static fn ($v) => is_numeric($v) ? number_format((float) $v, 6, '.', '') : self::escapePostgresString($v),
                $value,
            )) . '}';
        }

        // By default — JSON for MySQL and other platforms.
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * Escapes a string for a PostgreSQL array.
     */
    private static function escapePostgresString(string $value): string
    {
        // Wraps strings in quotes, escapes quotes inside.
        return '"' . str_replace('"', '\"', $value) . '"';
    }
}
