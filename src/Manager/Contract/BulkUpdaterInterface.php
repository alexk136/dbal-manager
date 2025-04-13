<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface BulkUpdaterInterface
{
    /**
     * Обновляет несколько строк в указанной таблице.
     *
     * @param string $tableName     Название таблицы.
     * @param array $paramsList     Список ассоциативных массивов с данными для обновления.
     * @param array|null $whereFields Поля, используемые для формирования условий WHERE. По умолчанию — первичный ключ.
     *
     * @return int Количество обновлённых строк.
     */
    public function updateMany(string $tableName, array $paramsList, ?array $whereFields = null): int;

    /**
     * Обновляет одну строку в указанной таблице.
     *
     * @param string $tableName     Название таблицы.
     * @param array $params         Ассоциативный массив с данными для обновления, включая значения ключей из whereFields.
     * @param array|null $whereFields Поля, используемые для формирования условий WHERE. По умолчанию — первичный ключ.
     *
     * @return int Количество обновлённых строк (0 или 1).
     */
    public function updateOne(string $tableName, array $params, ?array $whereFields = null): int;
}
