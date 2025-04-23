<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface BulkInserterInterface
{
    /**
     * Вставляет несколько строк в указанную таблицу.
     *
     * @param string $tableName название таблицы
     * @param array $paramsList список ассоциативных массивов с данными для вставки
     * @param bool $isIgnore игнорировать дубликаты при вставке (если поддерживается СУБД)
     *
     * @return int количество вставленных строк
     */
    public function insertMany(string $tableName, array $paramsList, bool $isIgnore = false): int;

    /**
     * Вставляет одну строку в указанную таблицу.
     *
     * @param string $tableName название таблицы
     * @param array $params ассоциативный массив с данными для вставки
     * @param bool $isIgnore игнорировать дубликаты при вставке (если поддерживается СУБД)
     *
     * @return int количество вставленных строк
     */
    public function insertOne(string $tableName, array $params, bool $isIgnore = false): int;
}
