<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

interface BulkUpserterInterface
{
    /**
     * Выполняет UPSERT (вставку или обновление) нескольких строк в указанной таблице.
     *
     * @param string $tableName название таблицы
     * @param array $paramsList список ассоциативных массивов с данными для вставки/обновления
     * @param array $replaceFields поля, которые должны быть обновлены при конфликте (обычно по уникальным ключам)
     *
     * @return int количество затронутых строк
     */
    public function upsertMany(string $tableName, array $paramsList, array $replaceFields): int;

    /**
     * Выполняет UPSERT (вставку или обновление) одной строки в указанной таблице.
     *
     * @param string $tableName название таблицы
     * @param array $params ассоциативный массив с данными для вставки/обновления
     * @param array $replaceFields поля, которые должны быть обновлены при конфликте (обычно по уникальным ключам)
     *
     * @return int количество затронутых строк (0 или 1)
     */
    public function upsertOne(string $tableName, array $params, array $replaceFields): int;
}
