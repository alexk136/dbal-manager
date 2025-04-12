<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface BulkUpserterInterface
{
    public function upsert(string $tableName, array $paramsList, array $replaceFields): int;
}
