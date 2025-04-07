<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use ITech\Bundle\DbalBundle\Config\ConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Service\Dto\DtoFieldExtractorInterface;
use ITech\Bundle\DbalBundle\Utils\DtoDeserializerInterface;

final class DbalManager
{
    public function __construct(
        private Connection $connection,
        private DtoDeserializerInterface $deserializer,
        private DtoFieldExtractorInterface $fieldStrategy,
        private DbalBundleConfig $config,
    ) {
        if (!$config) {
            $this->config = new DbalBundleConfig();
        }
    }

    /**
     * @throws Exception
     */
    public function findById(string|int $id, string $tableName, ?string $dtoClass = null, string $idField = ConfigurationInterface::ID_NAME): object|array|null
    {
        $fields = $dtoClass ? $this->fieldStrategy->getFields($dtoClass) : ['*'];

        $fieldList = implode(', ', $fields);
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = :id LIMIT 1',
            $fieldList,
            $tableName,
            $idField,
        );

        $stmt = $this->connection->executeQuery($sql, ['id' => $id]);

        return $this->deserializer->denormalize($stmt->fetchAssociative() ?: [], $dtoClass);
    }

    /**
     * @throws Exception
     */
    public function findByIdList(array $idList, string $tableName, ?string $dtoClass = null, string $idField = ConfigurationInterface::ID_NAME): array
    {
        if (empty($idList)) {
            return [];
        }

        $fields = $dtoClass ? $this->fieldStrategy->getFields($dtoClass) : ['*'];
        $fieldList = implode(', ', $fields);

        $placeholders = array_map(static fn ($i) => ":id_$i", array_keys($idList));
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s IN (%s)',
            $fieldList,
            $tableName,
            $idField,
            implode(', ', $placeholders),
        );

        $params = [];

        foreach ($idList as $i => $id) {
            $params["id_$i"] = $id;
        }

        $stmt = $this->connection->executeQuery($sql, $params);

        $rows = $stmt->fetchAllAssociative();

        return array_map(fn (array $row) => $this->deserializer->denormalize($row, $dtoClass), $rows);
    }

    /**
     * Выполнить SELECT и получить массив DTO.
     * @throws Exception
     */
    public function fetchAll(string $sql, array $params = [], ?string $dtoClass = null): iterable
    {
        $stmt = $this->connection->executeQuery($sql, $params);

        while ($row = $stmt->fetchAssociative()) {
            yield $dtoClass
                ? $this->deserializer->denormalize($row, $dtoClass)
                : $row;
        }
    }

    /**
     * Выполнить SELECT и получить одну запись.
     * @throws Exception
     */
    public function fetchOne(string $sql, array $params = [], ?string $dtoClass = null): object|array|null
    {
        $row = $this->connection->fetchAssociative($sql, $params);

        if ($row === false) {
            return null;
        }

        return $dtoClass
            ? $this->deserializer->denormalize($row, $dtoClass)
            : $row;
    }

    /**
     * Выполнить произвольный SQL-запрос
     * @throws Exception
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->connection->executeStatement($sql, $params);
    }

    /**
     * Упрощённая вставка.
     * @throws Exception
     */
    public function insert(string $table, array $data): void
    {
        $this->connection->insert($table, $data);
    }

    /**
     * Упрощённое обновление.
     * @throws Exception
     */
    public function update(string $table, array $data, array $criteria): void
    {
        $this->connection->update($table, $data, $criteria);
    }

    /**
     * Удаление строк.
     * @throws Exception
     */
    public function delete(string $table, array $criteria): void
    {
        $this->connection->delete($table, $criteria);
    }

    /**
     * Получить подключение к БД (например, для работы с транзакциями напрямую).
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
