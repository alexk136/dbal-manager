<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

use Doctrine\DBAL\Query\QueryBuilder;

interface DbalFinderInterface
{
    /**
     * Finds a single record using the provided QueryBuilder and optionally converts it to a DTO class.
     *
     * @param QueryBuilder $qb QueryBuilder instance for executing the query
     * @param string|null $dtoClass fully qualified class name of the DTO for converting the result, or null for raw data
     * @return object|array|null the found record as an object, array, or null if no record is found
     */
    public function findOne(QueryBuilder $qb, ?string $dtoClass = null): object|array|null;

    /**
     * Finds all records using the provided QueryBuilder and optionally converts them to a DTO class.
     *
     * @param QueryBuilder $qb QueryBuilder instance for executing the query
     * @param string|null $dtoClass fully qualified class name of the DTO for converting the results, or null for raw data
     * @return iterable iterator of the found records, each represented as an object or array
     */
    public function findAll(QueryBuilder $qb, ?string $dtoClass = null): iterable;

    /**
     * Finds a record by its ID in the specified table and optionally converts it to a DTO class.
     *
     * @param string|int $id ID of the record to find
     * @param string $tableName name of the table to search in
     * @param string|null $dtoClass fully qualified class name of the DTO for converting the result, or null for raw data
     * @param string $idField name of the ID field in the table
     * @return object|array|null the found record as an object, array, or null if no record is found
     */
    public function findById(string|int $id, string $tableName, ?string $dtoClass, string $idField): object|array|null;

    /**
     * Finds multiple records by their IDs in the specified table and optionally converts them to a DTO class.
     *
     * @param array $idList list of IDs of the records to find
     * @param string $tableName name of the table to search in
     * @param string|null $dtoClass fully qualified class name of the DTO for converting the results, or null for raw data
     * @param string $idField name of the ID field in the table
     * @return array an array of found records, each represented as an object or array
     */
    public function findByIdList(array $idList, string $tableName, ?string $dtoClass, string $idField): iterable;

    /**
     * Executes an SQL query and retrieves all matching records, optionally converting them to a DTO class.
     *
     * @param string $sql SQL query to execute
     * @param array $params parameters to bind to the query
     * @param string|null $dtoClass fully qualified class name of the DTO for converting the results, or null for raw data
     * @return iterable iterator of the found records, each represented as an object or array
     */
    public function fetchAllBySql(string $sql, array $params, ?string $dtoClass): iterable;

    /**
     * Executes an SQL query and retrieves a single matching record, optionally converting it to a DTO class.
     *
     * @param string $sql SQL query to execute
     * @param array $params parameters to bind to the query
     * @param string|null $dtoClass fully qualified class name of the DTO for converting the result, or null for raw data
     * @return object|array|null the found record as an object, array, or null if no record is found
     */
    public function fetchOneBySql(string $sql, array $params, ?string $dtoClass): object|array|null;

    /**
     * Counts the number of records in the specified table that match the given criteria.
     *
     * @param string $table name of the table to count records in
     * @param array $criteria associative array of criteria for filtering records (e.g., ['column' => 'value'])
     * @return int number of records matching the criteria
     */
    public function count(string $table, array $criteria = []): int;
}
