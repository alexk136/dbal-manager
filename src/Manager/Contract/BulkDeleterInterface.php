<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

interface BulkDeleterInterface
{
    /**
     * Удаляет одну запись из указанной таблицы по ID.
     *
     * @param string $tableName название таблицы
     * @param string|int $id ID записи для удаления
     *
     * @return int количество удаленных записей
     */
    public function deleteOne(string $tableName, string|int $id): int;

    /**
     * Удаляет несколько записей по массиву ID.
     *
     * @param string $tableName название таблицы
     * @param array $ids массив ID записей для удаления
     *
     * @return int количество удаленных записей
     */
    public function deleteMany(string $tableName, array $ids): int;

    /**
     * Удаляет несколько записей из указанной таблицы по массиву ID с использованием мягкого удаления.
     *
     * @param string $tableName название таблицы
     * @param array $ids массив ID записей для удаления
     *
     * @return int количество удаленных записей
     */
    public function deleteSoftMany(string $tableName, array $ids): int;

    /**
     * Удаляет одну запись из указанной таблицы по ID с использованием мягкого удаления.
     *
     * @param string $tableName название таблицы
     * @param string|int $ids ID записи для удаления
     *
     * @return int количество удаленных записей
     */
    public function deleteSoftOne(string $tableName, string|int $ids): int;
}
