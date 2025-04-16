<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Bulk;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException as UniqueConstraintViolationDbalException;
use Doctrine\DBAL\ParameterType;
use Exception;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Exception\UniqueConstraintViolationException;
use ITech\Bundle\DbalBundle\Exception\WriteDbalException;
use ITech\Bundle\DbalBundle\Manager\Contract\IdStrategy;
use ITech\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;
use ITech\Bundle\DbalBundle\Utils\IdGenerator;
use Symfony\Component\Uid\Uuid;

abstract class AbstractDbalWriteExecutor
{
    protected array $fieldNames;

    public function __construct(
        protected Connection $connection,
        protected SqlBuilderInterface $sqlBuilder,
        protected DbalBundleConfig $config,
    ) {
        $this->resetConfig();
    }

    /**
     * Важно! Переопределение списка полей следует использовать с осторожностью.
     */
    public function setFieldNames(array $fieldNames): static
    {
        $this->fieldNames = $fieldNames;

        return $this;
    }

    public function resetConfig(): static
    {
        $this->fieldNames = $this->config->fieldNames;

        return $this;
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
        $updatedAtField = $this->fieldNames[BundleConfigurationInterface::UPDATED_AT_NAME];

        if (!empty($updatedAtField)) {
            $row[$updatedAtField] = [$timestamp];
        }

        return $row;
    }

    protected function ensureCreatedAt(array $row, string $timestamp): array
    {
        $createdAtField = $this->config->fieldNames[BundleConfigurationInterface::CREATED_AT_NAME];

        if (!empty($createdAtField) && empty($row[$createdAtField])) {
            $row[$createdAtField] = [$timestamp];
        }

        return $row;
    }

    protected function ensureId(array $row): array
    {
        $idField = $this->fieldNames[BundleConfigurationInterface::ID_NAME] ?? BundleConfigurationInterface::ID_NAME;

        if (!isset($row[$idField])) {
            return $row;
        }

        $row[$idField] = match ($row[$idField]) {
            IdStrategy::UUID => [Uuid::v7(), ParameterType::STRING],
            IdStrategy::UID => [IdGenerator::generateUniqueId(), ParameterType::STRING],
            IdStrategy::INT => [random_int(1, PHP_INT_MAX), ParameterType::INTEGER],
            IdStrategy::STRING => ['id_' . uniqid(), ParameterType::STRING],
            IdStrategy::AUTO_INCREMENT => null,
            default => $row[$idField],
        };

        return $row;
    }

    protected function normalizeInsertData(array $rows): array
    {
        $currentTimestamp = date($this->config->defaultDateTimeFormat);

        return array_map(
            fn (array $row) => $this->normalizeInsertRow($row, $currentTimestamp),
            $rows,
        );
    }

    protected function normalizeUpdateParamsList(array $rows): array
    {
        $currentTimestamp = date($this->config->defaultDateTimeFormat);

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
