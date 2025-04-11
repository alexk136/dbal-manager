<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Placeholder;

interface PlaceholderStrategyInterface
{
    public function formatValue(mixed $value): string;
    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null): array;
}
