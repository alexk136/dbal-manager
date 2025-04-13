<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface BulkUpserterInterface
{
    /**
     * Выполняет UPSERT (вставку или обновление) нескольких строк в указанной таблице.
     *
     * @param string $tableName     Название таблицы.
     * @param array $paramsList     Список ассоциативных массивов с данными для вставки/обновления.
     * @param array $replaceFields  Поля, которые должны быть обновлены при конфликте (обычно по уникальным ключам).
     *
     * @return int Количество затронутых строк.
     */
    public function upsertMany(string $tableName, array $paramsList, array $replaceFields): int;

    /**
     * Выполняет UPSERT (вставку или обновление) одной строки в указанной таблице.
     *
     * @param string $tableName     Название таблицы.
     * @param array $params         Ассоциативный массив с данными для вставки/обновления.
     * @param array $replaceFields  Поля, которые должны быть обновлены при конфликте (обычно по уникальным ключам).
     *
     * @return int Количество затронутых строк (0 или 1).
     */
    public function upsertOne(string $tableName, array $params, array $replaceFields): int;
}
