<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

use Generator;

interface OffsetIteratorInterface
{
    public function iterate(
        string $sql,
        array $params,
        array $types,
        string $indexField,
        ?string $dtoClass,
    ): Generator;
}
