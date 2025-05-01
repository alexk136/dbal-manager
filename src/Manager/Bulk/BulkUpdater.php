<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Bulk;

use Elrise\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\BulkUpdaterInterface;
use Exception;

final class BulkUpdater extends AbstractDbalWriteExecutor implements BulkUpdaterInterface
{
    /**
     * @throws Exception
     */
    public function updateMany(string $tableName, array $paramsList, ?array $whereFields = null): int
    {
        if (empty($paramsList)) {
            return 0;
        }

        if ($whereFields === null) {
            $whereFields = [$this->fieldNames[BundleConfigurationInterface::ID_NAME]];
        }

        $totalUpdated = 0;

        foreach (array_chunk($paramsList, $this->chunkSize) as $chunk) {
            $normalizedList = $this->normalizeUpdateParamsList($chunk);
            $sql = $this->sqlBuilder->getUpdateBulkSql($tableName, $normalizedList, $whereFields);
            [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($normalizedList, $whereFields);
            $totalUpdated += $this->executeSql($sql, $flatParams, $types);
        }

        return $totalUpdated;
    }

    /**
     * @throws Exception
     */
    public function updateOne(string $tableName, array $params, ?array $whereFields = null): int
    {
        return $this->updateMany($tableName, [$params], $whereFields);
    }
}
