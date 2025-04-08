<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

use Generator;

interface CursorIteratorInterface
{
    public function iterate(
        string $tableName,
        string $cursorField,
        array $initialCursorValues,
        ?string $dtoClass,
    ): Generator;
}
