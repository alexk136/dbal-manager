<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface DbalMutatorInterface
{
    /**
     * Вставляет новую запись в указанную таблицу.
     *
     * @param string $table Название таблицы, в которую будет вставлена запись.
     * @param array $data Ассоциативный массив, где ключи - это названия столбцов, а значения - данные для вставки.
     * @return void
     */
    public function insert(string $table, array $data): void;

    /**
     * Выполняет SQL-запрос с указанными параметрами.
     *
     * @param string $sql SQL-запрос для выполнения.
     * @param array $params Необязательный ассоциативный массив параметров для подстановки в запрос.
     * @return int Количество затронутых строк.
     */
    public function execute(string $sql, array $params = []): int;

    /**
     * Обновляет существующие записи в указанной таблице на основе заданных критериев.
     *
     * @param string $table Название таблицы, в которой будут обновлены записи.
     * @param array $data Ассоциативный массив, где ключи - это названия столбцов, а значения - новые данные.
     * @param array $criteria Ассоциативный массив условий для идентификации записей, которые нужно обновить.
     * @return void
     */
    public function update(string $table, array $data, array $criteria): void;

    /**
     * Удаляет записи из указанной таблицы на основе заданных критериев.
     *
     * @param string $table Название таблицы, из которой будут удалены записи.
     * @param array $criteria Ассоциативный массив условий для идентификации записей, которые нужно удалить.
     * @return void
     */
    public function delete(string $table, array $criteria): void;
}
