<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

interface BulkUpserterInterface
{
    /**
     * Performs an UPSERT (insert or update) of multiple rows in the specified table.
     *
     * @param string $tableName name of the table
     * @param array $paramsList list of associative arrays with data to insert/update
     * @param array $replaceFields fields that should be updated on conflict (usually by unique keys)
     *
     * @return int number of affected rows
     */
    public function upsertMany(string $tableName, array $paramsList, array $replaceFields): int;

    /**
     * Performs an UPSERT (insert or update) of a single row in the specified table.
     *
     * @param string $tableName name of the table
     * @param array $params associative array with data to insert/update
     * @param array $replaceFields fields that should be updated on conflict (usually by unique keys)
     *
     * @return int number of affected rows (0 or 1)
     */
    public function upsertOne(string $tableName, array $params, array $replaceFields): int;
}
