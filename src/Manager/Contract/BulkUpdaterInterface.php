<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface BulkUpdaterInterface
{
    /**
     * Обновляет несколько строк в указанной таблице.
     *
     * @param string $tableName название таблицы
     * @param array $paramsList список ассоциативных массивов с данными для обновления
     * @param array|null $whereFields Поля, используемые для формирования условий WHERE. По умолчанию — первичный ключ.
     *
     * @return int количество обновлённых строк
     */
    public function updateMany(string $tableName, array $paramsList, ?array $whereFields = null): int;

    /**
     * Обновляет одну строку в указанной таблице.
     *
     * @param string $tableName название таблицы
     * @param array $params ассоциативный массив с данными для обновления, включая значения ключей из whereFields
     * @param array|null $whereFields Поля, используемые для формирования условий WHERE. По умолчанию — первичный ключ.
     *
     * @return int количество обновлённых строк (0 или 1)
     */
    public function updateOne(string $tableName, array $params, ?array $whereFields = null): int;
}
