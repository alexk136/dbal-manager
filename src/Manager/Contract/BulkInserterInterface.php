<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface BulkInserterInterface
{
    public function insert(string $tableName, array $paramsList, bool $isIgnore = false): int;
}
