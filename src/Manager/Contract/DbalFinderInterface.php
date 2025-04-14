<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface DbalFinderInterface
{
    /**
     * Находит запись по её ID в указанной таблице и, при необходимости, преобразует её в DTO-класс.
     *
     * @param string|int $id ID записи, которую нужно найти.
     * @param string $tableName Название таблицы, в которой выполняется поиск.
     * @param string|null $dtoClass Полное имя класса DTO для преобразования результата или null для получения сырых данных.
     * @param string $idField Название поля ID в таблице.
     * @return object|array|null Найденная запись в виде объекта, массива или null, если запись не найдена.
     */
    public function findById(string|int $id, string $tableName, ?string $dtoClass, string $idField): object|array|null;

    /**
     * Находит несколько записей по их ID в указанной таблице и, при необходимости, преобразует их в DTO-класс.
     *
     * @param array $idList Список ID записей, которые нужно найти.
     * @param string $tableName Название таблицы, в которой выполняется поиск.
     * @param string|null $dtoClass Полное имя класса DTO для преобразования результатов или null для получения сырых данных.
     * @param string $idField Название поля ID в таблице.
     * @return array Массив найденных записей, каждая из которых представлена объектом или массивом.
     */
    public function findByIdList(array $idList, string $tableName, ?string $dtoClass, string $idField): array;

    /**
     * Выполняет SQL-запрос и получает все подходящие записи, при необходимости преобразуя их в DTO-класс.
     *
     * @param string $sql SQL-запрос для выполнения.
     * @param array $params Параметры для подстановки в запрос.
     * @param string|null $dtoClass Полное имя класса DTO для преобразования результатов или null для получения сырых данных.
     * @return iterable Итератор найденных записей, каждая из которых представлена объектом или массивом.
     */
    public function fetchAll(string $sql, array $params, ?string $dtoClass): iterable;

    /**
     * Выполняет SQL-запрос и получает одну подходящую запись, при необходимости преобразуя её в DTO-класс.
     *
     * @param string $sql SQL-запрос для выполнения.
     * @param array $params Параметры для подстановки в запрос.
     * @param string|null $dtoClass Полное имя класса DTO для преобразования результата или null для получения сырых данных.
     * @return object|array|null Найденная запись в виде объекта, массива или null, если запись не найдена.
     */
    public function fetchOne(string $sql, array $params, ?string $dtoClass): object|array|null;
}
