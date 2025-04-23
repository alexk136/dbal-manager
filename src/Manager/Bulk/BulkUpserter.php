<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use Exception;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
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

        $replaceFields = $this->updateReplaceFields($replaceFields);
        $totalUpserted = 0;

        foreach (array_chunk($paramsList, $this->chunkSize) as $chunk) {
            $normalizedList = $this->normalizeInsertData($chunk);

            $sql = $this->sqlBuilder->getUpsertBulkSql($tableName, $normalizedList, $replaceFields);
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

    private function updateReplaceFields(array $replaceFields): array
    {
        $updatedAtField = $this->fieldNames[BundleConfigurationInterface::UPDATED_AT_NAME] ?? [];

        if (!in_array($updatedAtField, $replaceFields)) {
            $replaceFields[] = $updatedAtField;
        }

        return $replaceFields;
    }
}
