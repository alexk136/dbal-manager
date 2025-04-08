<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;

final readonly class BulkInserter implements BulkInserterInterface
{
    public function __construct(
        private Connection $connection,
        private DbalBundleConfig $config,
    ) {
    }

    public function insert(string $tableName, array $paramsList, bool $isIgnore = false): int
    {
        if (!$paramsList) {
            return 0;
        }
    }
}
