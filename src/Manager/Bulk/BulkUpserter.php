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
    public function upsert(string $tableName, array $paramsList, array $replaceFields): int
    {
        if (empty($paramsList)) {
            return 0;
        }

        $normalizedList = $this->normalizeInsertData($paramsList);

        $replaceFields = $this->updateReplaceFields($replaceFields);

        $sql = $this->sqlBuilder->getUpsertBulkSql($tableName, $normalizedList, $replaceFields);

        [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($normalizedList);

        return $this->executeSql($sql, $flatParams, $types);
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
