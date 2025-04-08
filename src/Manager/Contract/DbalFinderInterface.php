<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface DbalFinderInterface
{
    public function findById(string|int $id, string $tableName, ?string $dtoClass, string $idField): object|array|null;

    public function findByIdList(array $idList, string $tableName, ?string $dtoClass, string $idField): array;

    public function fetchAll(string $sql, array $params, ?string $dtoClass): iterable;

    public function fetchOne(string $sql, array $params, ?string $dtoClass): object|array|null;
}
