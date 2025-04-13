<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface BulkInserterInterface
{
    /**
     * Вставляет несколько строк в указанную таблицу.
     *
     * @param string $tableName  Название таблицы.
     * @param array $paramsList  Список ассоциативных массивов с данными для вставки.
     * @param bool $isIgnore     Игнорировать дубликаты при вставке (если поддерживается СУБД).
     *
     * @return int Количество вставленных строк.
     */
    public function insertMany(string $tableName, array $paramsList, bool $isIgnore = false): int;

    /**
     * Вставляет одну строку в указанную таблицу.
     *
     * @param string $tableName  Название таблицы.
     * @param array $params      Ассоциативный массив с данными для вставки.
     * @param bool $isIgnore     Игнорировать дубликаты при вставке (если поддерживается СУБД).
     *
     * @return int Количество вставленных строк.
     */
    public function insertOne(string $tableName, array $params, bool $isIgnore = false): int;
}
