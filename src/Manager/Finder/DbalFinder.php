<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Elrise\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\DbalFinderInterface;
use Elrise\Bundle\DbalBundle\Service\Serialize\DtoDeserializerInterface;
use Elrise\Bundle\DbalBundle\Utils\DbalTypeGuesser;
use Elrise\Bundle\DbalBundle\Utils\DtoFieldExtractor;

final readonly class DbalFinder implements DbalFinderInterface
{
    public function __construct(
        private Connection $connection,
        private DtoDeserializerInterface $deserializer,
    ) {
    }

    /**
     * @throws Exception
     */
    public function findOne(QueryBuilder $qb, ?string $dtoClass = null): object|array|null
    {
        $stmt = $qb->executeQuery();
        $row = $stmt->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return $dtoClass
            ? $this->deserializer->denormalize($row, $dtoClass)
            : $row;
    }

    /**
     * @throws Exception
     */
    public function findAll(QueryBuilder $qb, ?string $dtoClass = null): iterable
    {
        $stmt = $qb->executeQuery();

        while ($row = $stmt->fetchAssociative()) {
            yield $dtoClass
                ? $this->deserializer->denormalize($row, $dtoClass)
                : $row;
        }
    }

    /**
     * @throws Exception
     */
    public function findById(string|int $id, string $tableName, ?string $dtoClass = null, string $idField = BundleConfigurationInterface::ID_NAME): object|array|null
    {
        $fields = $dtoClass ? DtoFieldExtractor::getFields($dtoClass) : ['*'];

        $fieldList = implode(', ', $fields);
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = :id LIMIT 1',
            $fieldList,
            $tableName,
            $idField,
        );

        $stmt = $this->connection->executeQuery($sql, ['id' => $id]);

        $row = $stmt->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return $dtoClass
            ? $this->deserializer->denormalize($row, $dtoClass)
            : $row;
    }

    /**
     * @throws Exception
     */
    public function findByIdList(array $idList, string $tableName, ?string $dtoClass = null, string $idField = BundleConfigurationInterface::ID_NAME): iterable
    {
        if (empty($idList)) {
            return [];
        }

        $fields = $dtoClass ? DtoFieldExtractor::getFields($dtoClass) : ['*'];
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

        while ($row = $stmt->fetchAssociative()) {
            yield $dtoClass
                ? $this->deserializer->denormalize($row, $dtoClass)
                : $row;
        }
    }

    /**
     * Выполнить SELECT и получить массив DTO.
     * @throws Exception
     */
    public function fetchAllBySql(string $sql, array $params = [], ?string $dtoClass = null): iterable
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
    public function fetchOneBySql(string $sql, array $params = [], ?string $dtoClass = null): object|array|null
    {
        $normalizedSql = $this->normalizeSqlLimit($sql);

        $row = $this->connection->fetchAssociative($normalizedSql, $params);

        if ($row === false) {
            return null;
        }

        return $dtoClass
            ? $this->deserializer->denormalize($row, $dtoClass)
            : $row;
    }

    /**
     * @throws Exception
     */
    public function count(string $table, array $criteria = []): int
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->select('COUNT(*)')
            ->from($table);

        foreach ($criteria as $column => $value) {
            $param = ':' . $column;

            if (is_array($value)) {
                $qb->andWhere($qb->expr()->in($column, $param));
                $qb->setParameter($param, $value, DbalTypeGuesser::guessParameterType($value));
            } else {
                $qb->andWhere($qb->expr()->eq($column, $param));
                $qb->setParameter($param, $value);
            }
        }

        return (int) $qb->executeQuery()->fetchOne();
    }

    private function normalizeSqlLimit(string $sql): string
    {
        $sql = preg_replace('/\s+LIMIT\s+\d+(\s+OFFSET\s+\d+)?\s*$/i', '', $sql);

        return rtrim($sql, '; ') . ' LIMIT 1';
    }
}
