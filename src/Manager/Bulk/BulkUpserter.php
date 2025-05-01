<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use Exception;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkUpserterInterface;

final class BulkUpserter extends AbstractDbalWriteExecutor implements BulkUpserterInterface
{
    /**
     * @throws Exception
     */
    public function upsertMany(string $tableName, array $paramsList, array $replaceFields): int
    {
        if (empty($paramsList)) {
            return 0;
        }

        $totalUpserted = 0;

        foreach (array_chunk($paramsList, $this->chunkSize) as $chunk) {
            $normalizedList = $this->normalizeInsertData($chunk);
            $sql = $this->sqlBuilder->getUpsertBulkSql($tableName, $normalizedList, $replaceFields, $this->fieldNames);
            [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($normalizedList);
            $totalUpserted += $this->executeSql($sql, $flatParams, $types);
        }

        return $totalUpserted;
    }

    /**
     * @throws Exception
     */
    public function upsertOne(string $tableName, array $params, array $replaceFields): int
    {
        return $this->upsertMany($tableName, [$params], $replaceFields);
    }
}
