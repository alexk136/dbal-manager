<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use Exception;
use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;

final class BulkInserter extends AbstractDbalWriteExecutor implements BulkInserterInterface
{
    /**
     * @throws Exception
     */
    public function insertMany(string $tableName, array $paramsList, bool $isIgnore = false): int
    {
        if (!$paramsList) {
            return 0;
        }

        $firstKeys = array_keys($paramsList[0]);

        foreach ($paramsList as $i => $row) {
            if (array_keys($row) !== $firstKeys) {
                throw new InvalidArgumentException("Row #$i has mismatched fields in insert data.");
            }
        }

        $normalizedList = $this->normalizeInsertData($paramsList);

        $sql = $this->sqlBuilder->getInsertBulkSql($tableName, $normalizedList, $isIgnore);

        [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($normalizedList);

        return $this->executeSql($sql, $flatParams, $types);
    }

    /**
     * @throws Exception
     */
    public function insertOne(string $tableName, array $params, bool $isIgnore = false): int
    {
        return $this->insertMany($tableName, [$params], $isIgnore);
    }
}
