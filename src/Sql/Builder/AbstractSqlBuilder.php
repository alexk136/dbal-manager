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
     * Checks the table name to ensure it is a valid PostgreSQL identifier.
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
     * Checks the field names to ensure they are valid PostgreSQL identifiers.
     *
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
     * Limits the size of the SQL cache to prevent memory issues.
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
