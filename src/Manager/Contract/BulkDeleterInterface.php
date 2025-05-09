<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

interface BulkDeleterInterface
{
    /**
     * Deletes a single record from the specified table by ID.
     *
     * @param string $tableName name of the table
     * @param string|int $id ID of the record to delete
     *
     * @return int number of deleted records
     */
    public function deleteOne(string $tableName, string|int $id): int;

    /**
     * Deletes multiple records by an array of IDs.
     *
     * @param string $tableName name of the table
     * @param array $ids array of record IDs to delete
     *
     * @return int number of deleted records
     */
    public function deleteMany(string $tableName, array $ids): int;

    /**
     * Soft deletes multiple records from the specified table by an array of IDs.
     *
     * @param string $tableName name of the table
     * @param array $ids array of record IDs to delete
     *
     * @return int number of deleted records
     */
    public function deleteSoftMany(string $tableName, array $ids): int;

    /**
     * Soft deletes a single record from the specified table by ID.
     *
     * @param string $tableName name of the table
     * @param string|int $id ID of the record to delete
     *
     * @return int number of deleted records
     */
    public function deleteSoftOne(string $tableName, string|int $ids): int;
}
