<?php

declare(strict_types=1);

namespace ITech\DbalBundle\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use ITech\DbalBundle\Utils\DtoDeserializer;

final class DbalManager
{
    public function __construct(
        protected Connection $connection,
        protected DtoDeserializer $deserializer,
    ) {
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
