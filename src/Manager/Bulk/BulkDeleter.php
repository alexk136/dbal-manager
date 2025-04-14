<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use DateTimeImmutable;
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

        $paramsList = array_map(static fn ($id) => [$idFieldName => $id], $ids);

        [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($paramsList);

        return $this->executeSql($sql, $flatParams, $types);
    }

    /**
     * @throws Exception
     */
    public function deleteSoftMany(string $tableName, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $idFieldName = $this->fieldNames[BundleConfigurationInterface::ID_NAME];
        $deletedAtFieldName = $this->fieldNames[BundleConfigurationInterface::DELETED_AT_NAME];
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $paramsList = array_map(
            static fn ($id) => [
                $deletedAtFieldName => $now,
                $idFieldName => $id,
            ],
            $ids,
        );

        $sql = $this->sqlBuilder->getUpdateBulkSql($tableName, $paramsList, [$idFieldName]);

        [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($paramsList, [$idFieldName]);

        return $this->executeSql($sql, $flatParams, $types);
    }

    /**
     * @throws Exception
     */
    public function deleteSoftOne(string $tableName, string|int $ids): int
    {
        return $this->deleteSoftMany($tableName, [$ids]);
    }
}
