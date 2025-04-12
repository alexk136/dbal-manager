<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface BulkUpdaterInterface
{
    public function update(string $tableName, array $paramsList, ?array $whereFields = null): int;
}
