<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Builder;

interface SqlBuilderInterface
{
    /**
     * Генерирует SQL для массовой вставки (INSERT).
     */
    public function getInsertBulkSql(string $tableName, array $paramsList, bool $isIgnore = false): string;

    /**
     * Генерирует SQL для массового обновления (UPDATE).
     */
    public function getUpdateBulkSql(string $tableName, array $paramsList, array $whereFields): string;

    /**
     * Подготавливает параметры и where-условия для bulk-запросов.
     */
    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null): array;

    /**
     * Генерирует SQL для массовой вставки с обновлением при конфликте (UPSERT).
     */
    public function getUpsertBulkSql(string $tableName, array $paramsList, array $replaceFields): string;
}
