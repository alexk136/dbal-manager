<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Manager\Bulk\BulkInserter;
use ITech\Bundle\DbalBundle\Manager\Bulk\BulkUpdater;
use ITech\Bundle\DbalBundle\Manager\Bulk\BulkUpserter;
use ITech\Bundle\DbalBundle\Manager\Finder\DbalFinder;
use ITech\Bundle\DbalBundle\Manager\Iterator\CursorIterator;
use ITech\Bundle\DbalBundle\Manager\Iterator\OffsetIterator;
use ITech\Bundle\DbalBundle\Manager\Mutator\DbalMutator;
use ITech\Bundle\DbalBundle\Service\Serialize\DtoDeserializerInterface;
use ITech\Bundle\DbalBundle\Sql\Builder\SqlBuilderFactory;

final readonly class DbalManagerFactory
{
    public function __construct(
        private Connection $connection,
        private DtoDeserializerInterface $deserializer,
        private ?DbalBundleConfig $config = null,
    ) {
    }

    /**
     * @throws Exception
     */
    public function create(): DbalManager
    {
        $config = $this->config ?? new DbalBundleConfig();

        $sqlBuilder = (new SqlBuilderFactory(
            $this->connection,
            $config->placeholderStrategy,
        ))->create();

        $finder = new DbalFinder($this->connection, $this->deserializer);
        $mutator = new DbalMutator($this->connection);
        $cursorIterator = new CursorIterator($this->connection, $this->deserializer, $config);
        $offsetIterator = new OffsetIterator($this->connection, $this->deserializer, $config);

        $bulkInserter = new BulkInserter($this->connection, $sqlBuilder, $config);
        $bulkUpdater = new BulkUpdater($this->connection, $sqlBuilder, $config);
        $bulkUpserter = new BulkUpserter($this->connection, $sqlBuilder, $config);

        return new DbalManager(
            $finder,
            $mutator,
            $cursorIterator,
            $offsetIterator,
            $bulkInserter,
            $bulkUpdater,
            $bulkUpserter
        );
    }
}
