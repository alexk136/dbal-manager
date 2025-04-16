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
use ITech\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;

final readonly class DbalManagerFactory
{
    public function __construct(
        private Connection $defaultConnection,
        private DtoDeserializerInterface $deserializer,
        private ?DbalBundleConfig $defaultConfig = null,
    ) {
    }

    public function createFinder(?Connection $connection = null): DbalFinder
    {
        return new DbalFinder(
            $connection ?? $this->defaultConnection,
            $this->deserializer,
        );
    }

    public function createMutator(?Connection $connection = null): DbalMutator
    {
        return new DbalMutator($connection ?? $this->defaultConnection);
    }

    public function createCursorIterator(?Connection $connection = null, ?DbalBundleConfig $config = null): CursorIterator
    {
        return new CursorIterator(
            $connection ?? $this->defaultConnection,
            $this->deserializer,
            $this->getConfig($config),
        );
    }

    public function createOffsetIterator(?Connection $connection = null, ?DbalBundleConfig $config = null): OffsetIterator
    {
        return new OffsetIterator(
            $connection ?? $this->defaultConnection,
            $this->deserializer,
            $this->getConfig($config),
        );
    }

    /**
     * @throws Exception
     */
    public function createSqlBuilder(Connection $connection, ?DbalBundleConfig $config = null): SqlBuilderInterface
    {
        return (new SqlBuilderFactory(
            $connection,
            $this->getConfig($config)->placeholderStrategy,
        ))->create();
    }

    /**
     * @throws Exception
     */
    public function createBulkInserter(?Connection $connection = null, ?DbalBundleConfig $config = null): BulkInserter
    {
        $conn = $connection ?? $this->defaultConnection;

        return new BulkInserter($conn, $this->createSqlBuilder($conn, $config), $this->getConfig($config));
    }

    /**
     * @throws Exception
     */
    public function createBulkUpdater(?Connection $connection = null, ?DbalBundleConfig $config = null): BulkUpdater
    {
        $conn = $connection ?? $this->defaultConnection;

        return new BulkUpdater($conn, $this->createSqlBuilder($conn, $config), $this->getConfig($config));
    }

    /**
     * @throws Exception
     */
    public function createBulkUpserter(?Connection $connection = null, ?DbalBundleConfig $config = null): BulkUpserter
    {
        $conn = $connection ?? $this->defaultConnection;

        return new BulkUpserter($conn, $this->createSqlBuilder($conn, $config), $this->getConfig($config));
    }

    /**
     * @throws Exception
     */
    public function createManager(?Connection $connection = null, ?DbalBundleConfig $config = null): DbalManager
    {
        $conn = $connection ?? $this->defaultConnection;

        return new DbalManager(
            $this->createFinder($conn),
            $this->createMutator($conn),
            $this->createCursorIterator($conn, $config),
            $this->createOffsetIterator($conn, $config),
            $this->createBulkInserter($conn, $config),
            $this->createBulkUpdater($conn, $config),
            $this->createBulkUpserter($conn, $config),
        );
    }

    private function getConfig(?DbalBundleConfig $config): DbalBundleConfig
    {
        return $config ?? $this->defaultConfig ?? new DbalBundleConfig();
    }
}
