<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Bulk;

use Elrise\Bundle\DbalBundle\Enum\BulkInserterInterface;
use Exception;
use InvalidArgumentException;

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

        $totalInserted = 0;

        foreach (array_chunk($paramsList, $this->chunkSize) as $chunk) {
            $normalizedList = $this->normalizeInsertData($chunk);
            $sql = $this->sqlBuilder->getInsertBulkSql($tableName, $normalizedList, $isIgnore);
            [$flatParams, $types] = $this->sqlBuilder->prepareBulkParameterLists($normalizedList);
            $totalInserted += $this->executeSql($sql, $flatParams, $types);
        }

        return $totalInserted;
    }

    /**
     * @throws Exception
     */
    public function insertOne(string $tableName, array $params, bool $isIgnore = false): int
    {
        return $this->insertMany($tableName, [$params], $isIgnore);
    }
}
