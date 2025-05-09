<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Sql\Builder;

interface SqlBuilderInterface
{
    /**
     * Generates SQL for bulk insert (INSERT).
     *
     * @param string $tableName name of the table
     * @param array $paramsList list of associative arrays with data to insert
     * @param bool $isIgnore whether to ignore duplicates during insertion (optional)
     * @return string generated SQL query
     */
    public function getInsertBulkSql(string $tableName, array $paramsList, bool $isIgnore = false): string;

    /**
     * Generates SQL for bulk update (UPDATE).
     *
     * @param string $tableName name of the table
     * @param array $paramsList list of associative arrays with data to update
     * @param array $whereFields fields to be used in the WHERE condition
     * @return string generated SQL query
     */
    public function getUpdateBulkSql(string $tableName, array $paramsList, array $whereFields): string;

    /**
     * Prepares parameters and WHERE conditions for bulk queries.
     *
     * @param array $batchRows list of rows to process
     * @param array|null $whereFields optional array of fields for WHERE condition
     * @return array prepared parameters and conditions
     */
    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null): array;

    /**
     * Generates SQL for bulk insert with conflict update (UPSERT).
     *
     * @param string $tableName name of the table
     * @param array $paramsList list of associative arrays with data to insert or update
     * @param array $replaceFields fields to be updated on conflict
     * @param array $fieldNames optional list of field names to match (default empty)
     * @return string generated SQL query
     */
    public function getUpsertBulkSql(string $tableName, array $paramsList, array $replaceFields, array $fieldNames = []): string;

    /**
     * Generates SQL for bulk delete.
     *
     * @param string $tableName name of the table
     * @param array $idList list of record IDs to delete
     * @return string generated SQL query
     */
    public function getDeleteBulkSql(string $tableName, array $idList): string;
}
