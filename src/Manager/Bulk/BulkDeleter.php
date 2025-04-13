<?php

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use Exception;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkDeleterInterface;

class BulkDeleter extends AbstractDbalWriteExecutor implements BulkDeleterInterface
{

    public function deleteOne(string $tableName, string|int $id): int
    {
        return $this->deleteMany($tableName, [$id]);
    }

    /**
     * @throws Exception
     */
    public function deleteMany(string $tableName, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $sql = $this->sqlBuilder->getDeleteBulkSql($tableName, $ids);

        $idFieldName = $this->fieldNames[BundleConfigurationInterface::ID_NAME];

        $paramsList = array_map(static fn($id) => [$idFieldName => $id], $ids);

        [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($paramsList);

        return $this->executeSql($sql, $flatParams, $types);
    }
}