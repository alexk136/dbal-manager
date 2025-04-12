<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException as UniqueConstraintViolationDbalException;
use Exception;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Exception\UniqueConstraintViolationException;
use ITech\Bundle\DbalBundle\Exception\WriteDbalException;
use ITech\Bundle\DbalBundle\Service\Generator\IdGenerator;
use ITech\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;

abstract class AbstractDbalWriteExecutor
{
    public function __construct(
        protected Connection $connection,
        protected DbalBundleConfig $config,
        protected SqlBuilderInterface $sqlBuilder,
    ) {
    }

    /**
     * @throws Exception
     */
    protected function executeSql(string $sql, array $params, array $types): int
    {
        try {
            return $this->connection->executeStatement($sql, $params, $types);
        } catch (DbalException $e) {
            throw $this->mapDbalException($e);
        }
    }

    protected function mapDbalException(DbalException $e): Exception
    {
        $message = $e->getMessage();

        if ($e instanceof UniqueConstraintViolationDbalException) {
            if (preg_match('/Duplicate entry \'(.*?)\' for key \'.*?\.(.*?)\'/', $message, $matches)) {
                $notUniqueValues = explode('-', $matches[1]);
                $constraintName = $matches[2];

                return new UniqueConstraintViolationException($constraintName, $notUniqueValues, $e);
            }

            return new WriteDbalException($e);
        }

        if ($e instanceof DriverException) {
            if (preg_match('/Check constraint \'(.*?)\' is violated/', $message, $matches)) {
                return new ConstraintViolationException($e, null);
            }

            return new WriteDbalException($e);
        }

        return new WriteDbalException($e);
    }

    protected function setUpdatedAt(array $row, string $timestamp): array
    {
        $updatedAtField = $this->config->fieldNames[BundleConfigurationInterface::UPDATED_AT_NAME];
        $row[$updatedAtField] = [$timestamp];

        return $row;
    }

    protected function ensureCreatedAt(array $row, string $timestamp): array
    {
        $createdAtField = $this->config->fieldNames[BundleConfigurationInterface::CREATED_AT_NAME];

        if (empty($row[$createdAtField])) {
            $row[$createdAtField] = [$timestamp];
        }

        return $row;
    }

    protected function ensureId(array $row): array
    {
        $idField = $this->config->fieldNames[BundleConfigurationInterface::ID_NAME];

        if (empty($row[$idField])) {
            $row[$idField] = [IdGenerator::generateUniqueId()];
        }

        return $row;
    }

    protected function normalizeInsertData(array $rows): array
    {
        $currentTimestamp = date('Y-m-d H:i:s');

        return array_map(
            fn (array $row) => $this->normalizeInsertRow($row, $currentTimestamp),
            $rows,
        );
    }

    protected function normalizeUpdateParamsList(array $rows): array
    {
        $currentTimestamp = date('Y-m-d H:i:s');

        return array_map(
            fn (array $row) => $this->setUpdatedAt($row, $currentTimestamp),
            $rows,
        );
    }

    private function normalizeInsertRow(array $row, string $timestamp): array
    {
        $row = $this->ensureId($row);
        $row = $this->ensureCreatedAt($row, $timestamp);

        return $this->setUpdatedAt($row, $timestamp);
    }
}
