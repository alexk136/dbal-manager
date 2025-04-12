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
    public function update(string $tableName, array $paramsList, ?array $whereFields = null): int
    {
        if (empty($paramsList)) {
            return 0;
        }

        if ($whereFields === null) {
            $whereFields = [$this->config->fieldNames[BundleConfigurationInterface::ID_NAME]];
        }

        $normalizedList = $this->normalizeUpdateParamsList($paramsList);

        $sql = $this->sqlBuilder->getUpdateBulkSql($tableName, $normalizedList, $whereFields);

        [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($normalizedList, $whereFields);

        return $this->executeSql($sql, $flatParams, $types);
    }
}
