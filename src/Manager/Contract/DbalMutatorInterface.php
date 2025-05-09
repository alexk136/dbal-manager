<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

interface DbalMutatorInterface
{
    /**
     * Inserts a new record into the specified table.
     *
     * @param string $table name of the table to insert the record into
     * @param array $data associative array where keys are column names and values are the data to insert
     */
    public function insert(string $table, array $data): void;

    /**
     * Executes an SQL query with the specified parameters.
     *
     * @param string $sql SQL query to execute
     * @param array $params optional associative array of parameters to bind to the query
     * @return int number of affected rows
     */
    public function execute(string $sql, array $params = []): int;

    /**
     * Updates existing records in the specified table based on the given criteria.
     *
     * @param string $table name of the table where records will be updated
     * @param array $data associative array where keys are column names and values are the new data
     * @param array $criteria associative array of conditions to identify the records to update
     */
    public function update(string $table, array $data, array $criteria): void;

    /**
     * Deletes records from the specified table based on the given criteria.
     *
     * @param string $table name of the table from which records will be deleted
     * @param array $criteria associative array of conditions to identify the records to delete
     */
    public function delete(string $table, array $criteria): void;
}
