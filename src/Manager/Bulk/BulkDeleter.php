<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use DateTimeImmutable;
use Exception;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkDeleterInterface;

final class BulkDeleter extends AbstractDbalWriteExecutor implements BulkDeleterInterface
{
    /**
     * @throws Exception
     */
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

        $totalDeleted = 0;
        $idFieldName = $this->fieldNames[BundleConfigurationInterface::ID_NAME];

        foreach (array_chunk($ids, $this->chunkSize) as $chunk) {
            $sql = $this->sqlBuilder->getDeleteBulkSql($tableName, $chunk);
            $paramsList = array_map(static fn ($id) => [$idFieldName => $id], $chunk);
            [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($paramsList);
            $totalDeleted += $this->executeSql($sql, $flatParams, $types);
        }

        return $totalDeleted;
    }

    /**
     * @throws Exception
     */
    public function deleteSoftMany(string $tableName, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $totalUpdated = 0;
        $idFieldName = $this->fieldNames[BundleConfigurationInterface::ID_NAME];
        $deletedAtFieldName = $this->fieldNames[BundleConfigurationInterface::DELETED_AT_NAME];
        $now = (new DateTimeImmutable())->format($this->config->defaultDateTimeFormat);

        foreach (array_chunk($ids, $this->chunkSize) as $chunk) {
            $paramsList = array_map(
                static fn ($id) => [
                    $deletedAtFieldName => $now,
                    $idFieldName => $id,
                ],
                $chunk,
            );

            $sql = $this->sqlBuilder->getUpdateBulkSql($tableName, $paramsList, [$idFieldName]);
            [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($paramsList, [$idFieldName]);
            $totalUpdated += $this->executeSql($sql, $flatParams, $types);
        }

        return $totalUpdated;
    }

    /**
     * @throws Exception
     */
    public function deleteSoftOne(string $tableName, string|int $ids): int
    {
        return $this->deleteSoftMany($tableName, [$ids]);
    }
}
