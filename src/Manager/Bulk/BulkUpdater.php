<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use Exception;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkUpdaterInterface;

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

        $normalizedList = $this->normalizeUpdateParamsList($paramsList);

        $sql = $this->sqlBuilder->getUpdateBulkSql($tableName, $normalizedList, $whereFields);

        [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($normalizedList, $whereFields);

        return $this->executeSql($sql, $flatParams, $types);
    }

    /**
     * @throws Exception
     */
    public function updateOne(string $tableName, array $params, ?array $whereFields = null): int
    {
        return $this->updateMany($tableName, [$params], $whereFields);
    }
}
