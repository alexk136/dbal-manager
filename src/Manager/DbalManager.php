<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Generator;
use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Config\ConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Service\Dto\DtoFieldExtractorInterface;
use ITech\Bundle\DbalBundle\Service\Serialize\DtoDeserializerInterface;
use RuntimeException;

final class DbalManager
{
    public function __construct(
        private Connection $connection,
        private DtoDeserializerInterface $deserializer,
        private DtoFieldExtractorInterface $dtoFieldExtractor,
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
        $fields = $dtoClass ? $this->dtoFieldExtractor->getFields($dtoClass) : ['*'];

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

        $fields = $dtoClass ? $this->dtoFieldExtractor->getFields($dtoClass) : ['*'];
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
     * @throws Exception
     */
    public function iterateByCursor(
        string $tableName,
        string $cursorField = ConfigurationInterface::ID_NAME,
        array $initialCursorValues = [0],
        ?string $dtoClass = null,
    ): Generator {
        if (empty($cursorField)) {
            throw new InvalidArgumentException('Cursor field must be defined.');
        }

        $fieldList = $dtoClass !== null
            ? implode(', ', $this->dtoFieldExtractor->getFields($dtoClass))
            : '*';

        $cursorValues = $initialCursorValues;

        while (true) {
            $sql = sprintf(
                'SELECT %s FROM %s WHERE %s > :cursor ORDER BY %s %s LIMIT :limit',
                $fieldList,
                $tableName,
                $cursorField,
                $cursorField,
                $this->config->orderDirection,
            );

            $cursor = $cursorValues[0];

            $params = [
                'cursor' => $cursor,
                'limit' => $this->config->chunkSize,
            ];

            $types = [
                'cursor' => $this->getParameterType($cursor),
                'limit' => ParameterType::INTEGER,
            ];

            $stmt = $this->connection->executeQuery($sql, $params, $types);

            $rows = $stmt->fetchAllAssociative();

            $stmt->free();

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                if ($dtoClass !== null) {
                    $dto = $this->deserializer->denormalize($row, $dtoClass);
                    $cursorValues = [$this->dtoFieldExtractor->getFieldValue($dto, $cursorField)];
                    yield $dto;
                } else {
                    $cursorValue = $row[$cursorField] ?? null;

                    if ($cursorValue === null) {
                        throw new RuntimeException("Missing cursor field '{$cursorField}' in raw row data.");
                    }

                    $cursorValues = [$cursorValue];
                    yield $row;
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function iterateByOffset(
        string $sql,
        array $params = [],
        array $types = [],
        string $indexField = ConfigurationInterface::ID_NAME,
        ?string $dtoClass = null,
    ): Generator {
        $offset = 0;

        while (true) {
            $params['limit'] = $this->config->chunkSize;
            $params['offset'] = $offset;

            $types['limit'] = ParameterType::INTEGER;
            $types['offset'] = ParameterType::INTEGER;

            $pagedSql = $sql . ' ORDER BY ' . $indexField . ' LIMIT :limit OFFSET :offset';
            $stmt = $this->connection->executeQuery($pagedSql, $params, $types);

            $rows = $stmt->fetchAllAssociative();

            $stmt->free();

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                if ($dtoClass !== null) {
                    yield $this->deserializer->denormalize($row, $dtoClass);
                } else {
                    yield $row;
                }
            }

            $offset += $this->config->chunkSize;
        }
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

    private function getParameterType(mixed $value): ParameterType
    {
        return match (true) {
            is_int($value) => ParameterType::INTEGER,
            is_bool($value) => ParameterType::BOOLEAN,
            default => ParameterType::STRING,
        };
    }
}
