<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Bulk;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException as UniqueConstraintViolationDbalException;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Elrise\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use Elrise\Bundle\DbalBundle\Config\DbalBundleConfig;
use Elrise\Bundle\DbalBundle\DBAL\DbalParameterType;
use Elrise\Bundle\DbalBundle\Enum\DbalConfigurableExecutorInterface;
use Elrise\Bundle\DbalBundle\Enum\IdStrategy;
use Elrise\Bundle\DbalBundle\Exception\UniqueConstraintViolationException;
use Elrise\Bundle\DbalBundle\Exception\WriteDbalException;
use Elrise\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;
use Elrise\Bundle\DbalBundle\Utils\IdGenerator;
use Exception;
use Symfony\Component\Uid\Uuid;

abstract class AbstractDbalWriteExecutor implements DbalConfigurableExecutorInterface
{
    protected array $fieldNames;
    protected int $chunkSize;

    public function __construct(
        protected Connection $connection,
        protected SqlBuilderInterface $sqlBuilder,
        protected DbalBundleConfig $config,
    ) {
        $this->resetConfig();
    }

    public function setChunkSize(int $chunkSize): static
    {
        $this->chunkSize = $chunkSize;

        return $this;
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
        $this->chunkSize = $this->config->chunkSize;

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

    /**
     * @throws WriteDbalException
     */
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
            $row[$updatedAtField] = [$timestamp, DbalParameterType::STRING];
        }

        return $row;
    }

    protected function ensureCreatedAt(array $row, string $timestamp): array
    {
        $createdAtField = $this->config->fieldNames[BundleConfigurationInterface::CREATED_AT_NAME];

        if (!empty($createdAtField) && empty($row[$createdAtField])) {
            $row[$createdAtField] = [$timestamp, DbalParameterType::STRING];
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
            IdStrategy::UUID => [Uuid::v7(), DbalParameterType::STRING],
            IdStrategy::UID => [IdGenerator::generateUniqueId(), DbalParameterType::STRING],
            IdStrategy::INT => [random_int(1, PHP_INT_MAX), DbalParameterType::INTEGER],
            IdStrategy::STRING => ['id_' . uniqid(), DbalParameterType::STRING],
            IdStrategy::AUTO_INCREMENT,
            IdStrategy::DEFAULT => $this->isPostgres() ? DbalParameterType::default() : [null, DbalParameterType::NULL],
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

    private function isPostgres(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform;
    }
}
