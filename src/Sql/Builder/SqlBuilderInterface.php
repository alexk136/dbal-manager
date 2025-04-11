<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Builder;

interface SqlBuilderInterface
{
    public function getInsertBulkSql(string $tableName, array $paramsList, bool $isIgnore = false): string;
    public function getUpdateBulkSql(string $tableName, array $paramsList, array $whereFields): string;
    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null): array;
}
