<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Utils;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use JsonException;

final class ArraySerializer
{
    /**
     * Сериализует массив под конкретную платформу.
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

        // По умолчанию — JSON для MySQL и других платформ
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * Экранирует строку для PostgreSQL массива.
     */
    private static function escapePostgresString(string $value): string
    {
        // Оборачивает строки в кавычки, экранирует кавычки внутри
        return '"' . str_replace('"', '\"', $value) . '"';
    }
}
