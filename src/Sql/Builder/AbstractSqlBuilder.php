<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Sql\Builder;

use Elrise\Bundle\DbalBundle\Sql\Placeholder\PlaceholderStrategyInterface;
use InvalidArgumentException;

abstract class AbstractSqlBuilder implements SqlBuilderInterface
{
    protected const int CACHE_LIMIT = 1000;
    protected array $sqlCache = [];

    public function __construct(
        protected readonly PlaceholderStrategyInterface $placeholder,
    ) {
    }

    /**
     * Проверяет имя таблицы, чтобы убедиться, что оно является допустимым идентификатором PostgreSQL.
     *
     * @throws InvalidArgumentException
     */
    protected function validateTableName(string $tableName): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            throw new InvalidArgumentException("Invalid table name: $tableName");
        }
    }

    /**
     * Проверяет имена полей, чтобы убедиться, что они являются допустимыми идентификаторами PostgreSQL.
     * @param array<string> $fields
     * @throws InvalidArgumentException
     */
    protected function validateFieldNames(array $fields): void
    {
        foreach ($fields as $field) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
                throw new InvalidArgumentException("Invalid field name: $field");
            }
        }
    }

    /**
     * Ограничивает размер SQL-кеша, чтобы предотвратить проблемы с памятью.
     */
    protected function limitCacheSize(): void
    {
        if (count($this->sqlCache) > self::CACHE_LIMIT) {
            // Remove the oldest entry (FIFO)
            array_shift($this->sqlCache);
        }
    }

    protected function getValues(array $row): array
    {
        return array_map(fn ($value) => $this->placeholder->formatValue($value), $row);
    }
}
