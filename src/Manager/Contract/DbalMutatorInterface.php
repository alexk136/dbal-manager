<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface DbalMutatorInterface
{
    /**
     * Вставляет новую запись в указанную таблицу.
     *
     * @param string $table название таблицы, в которую будет вставлена запись
     * @param array $data ассоциативный массив, где ключи - это названия столбцов, а значения - данные для вставки
     */
    public function insert(string $table, array $data): void;

    /**
     * Выполняет SQL-запрос с указанными параметрами.
     *
     * @param string $sql SQL-запрос для выполнения
     * @param array $params необязательный ассоциативный массив параметров для подстановки в запрос
     * @return int количество затронутых строк
     */
    public function execute(string $sql, array $params = []): int;

    /**
     * Обновляет существующие записи в указанной таблице на основе заданных критериев.
     *
     * @param string $table название таблицы, в которой будут обновлены записи
     * @param array $data ассоциативный массив, где ключи - это названия столбцов, а значения - новые данные
     * @param array $criteria ассоциативный массив условий для идентификации записей, которые нужно обновить
     */
    public function update(string $table, array $data, array $criteria): void;

    /**
     * Удаляет записи из указанной таблицы на основе заданных критериев.
     *
     * @param string $table название таблицы, из которой будут удалены записи
     * @param array $criteria ассоциативный массив условий для идентификации записей, которые нужно удалить
     */
    public function delete(string $table, array $criteria): void;
}
