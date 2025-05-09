<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

interface BulkUpdaterInterface
{
    /**
     * Updates multiple rows in the specified table.
     *
     * @param string $tableName name of the table
     * @param array $paramsList list of associative arrays with data to update
     * @param array|null $whereFields fields used to form the WHERE conditions. Defaults to primary key.
     *
     * @return int number of rows updated
     */
    public function updateMany(string $tableName, array $paramsList, ?array $whereFields = null): int;

    /**
     * Updates a single row in the specified table.
     *
     * @param string $tableName name of the table
     * @param array $params associative array with data to update, including key values from whereFields
     * @param array|null $whereFields fields used to form the WHERE conditions. Defaults to primary key.
     *
     * @return int number of rows updated (0 or 1)
     */
    public function updateOne(string $tableName, array $params, ?array $whereFields = null): int;
}
