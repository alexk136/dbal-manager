<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

interface BulkInserterInterface
{
    /**
     * Inserts multiple rows into the specified table.
     *
     * @param string $tableName name of the table
     * @param array $paramsList list of associative arrays with data to insert
     * @param bool $isIgnore whether to ignore duplicates during insertion (if supported by the DBMS)
     *
     * @return int number of rows inserted
     */
    public function insertMany(string $tableName, array $paramsList, bool $isIgnore = false): int;

    /**
     * Inserts a single row into the specified table.
     *
     * @param string $tableName name of the table
     * @param array $params associative array with data to insert
     * @param bool $isIgnore whether to ignore duplicates during insertion (if supported by the DBMS)
     *
     * @return int number of rows inserted
     */
    public function insertOne(string $tableName, array $params, bool $isIgnore = false): int;
}
