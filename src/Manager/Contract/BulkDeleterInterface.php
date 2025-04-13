<?php

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface BulkDeleterInterface
{
    /**
     * Удаляет одну запись из указанной таблицы по ID.
     */
    public function deleteOne(string $tableName, string $id): int;

    /**
     * Удаляет несколько записей по массиву ID.
     */
    public function deleteMany(string $tableName, array $ids): int;
}