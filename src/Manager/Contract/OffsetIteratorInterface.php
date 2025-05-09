<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

use Generator;

interface OffsetIteratorInterface
{
    /**
     * Iterates over the query results using an offset-based approach.
     *
     * @param string $sql SQL query to execute
     * @param array $params associative array of parameters to bind to the query
     * @param array $types associative array of parameter types for the query
     * @param string $indexField name of the field used as the index for iteration
     * @param string|null $dtoClass fully qualified class name of the DTO for converting the results, or null for raw data
     * @return Generator generator that returns the query results, each represented as an object or array
     */
    public function iterate(
        string $sql,
        array $params,
        array $types,
        string $indexField,
        ?string $dtoClass,
    ): Generator;
}
