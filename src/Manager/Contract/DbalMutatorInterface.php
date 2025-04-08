<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface DbalMutatorInterface
{
    public function insert(string $table, array $data): void;
    public function execute(string $sql, array $params = []): int;
    public function update(string $table, array $data, array $criteria): void;
    public function delete(string $table, array $criteria): void;
}
