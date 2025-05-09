<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

use Generator;

interface CursorIteratorInterface
{
    /**
     * Iterates over query results using a cursor-based approach.
     *
     * @param string $tableName name of the table from which the query is executed
     * @param string $cursorField name of the field used as the cursor for iteration
     * @param array $initialCursorValues associative array of initial cursor values to start the iteration
     * @param string|null $dtoClass fully qualified class name of the DTO for converting results, or null for raw data
     * @return Generator a generator returning query results, each represented as an object or array
     */
    public function iterate(
        string $tableName,
        string $cursorField,
        array $initialCursorValues,
        ?string $dtoClass,
    ): Generator;
}
