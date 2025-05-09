<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Sql\Placeholder;

use InvalidArgumentException;

interface PlaceholderStrategyInterface
{
    public function formatValue(mixed $value): string;

    /**
     * Generates a flat list of parameters and types for bulk insert/update operations.
     *
     * @param array $batchRows set of rows to process (array of arrays with values)
     * @param array|null $whereFields list of fields for the WHERE condition (if required)
     * @param string|null $platform database platform for serializing arrays
     *
     * @return array{0: array<int, mixed>, 1: array<int, int>} array of parameters and corresponding types
     *
     * @throws InvalidArgumentException if $batchRows is empty
     */
    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null, ?string $platform = null): array;
}
