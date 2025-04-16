<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface DbalFinderInterface
{
    /**
     * Находит запись по её ID в указанной таблице и, при необходимости, преобразует её в DTO-класс.
     *
     * @param string|int $id ID записи, которую нужно найти
     * @param string $tableName название таблицы, в которой выполняется поиск
     * @param string|null $dtoClass полное имя класса DTO для преобразования результата или null для получения сырых данных
     * @param string $idField название поля ID в таблице
     * @return object|array|null найденная запись в виде объекта, массива или null, если запись не найдена
     */
    public function findById(string|int $id, string $tableName, ?string $dtoClass, string $idField): object|array|null;

    /**
     * Находит несколько записей по их ID в указанной таблице и, при необходимости, преобразует их в DTO-класс.
     *
     * @param array $idList список ID записей, которые нужно найти
     * @param string $tableName название таблицы, в которой выполняется поиск
     * @param string|null $dtoClass полное имя класса DTO для преобразования результатов или null для получения сырых данных
     * @param string $idField название поля ID в таблице
     * @return array массив найденных записей, каждая из которых представлена объектом или массивом
     */
    public function findByIdList(array $idList, string $tableName, ?string $dtoClass, string $idField): array;

    /**
     * Выполняет SQL-запрос и получает все подходящие записи, при необходимости преобразуя их в DTO-класс.
     *
     * @param string $sql SQL-запрос для выполнения
     * @param array $params параметры для подстановки в запрос
     * @param string|null $dtoClass полное имя класса DTO для преобразования результатов или null для получения сырых данных
     * @return iterable итератор найденных записей, каждая из которых представлена объектом или массивом
     */
    public function fetchAllBySql(string $sql, array $params, ?string $dtoClass): iterable;

    /**
     * Выполняет SQL-запрос и получает одну подходящую запись, при необходимости преобразуя её в DTO-класс.
     *
     * @param string $sql SQL-запрос для выполнения
     * @param array $params параметры для подстановки в запрос
     * @param string|null $dtoClass полное имя класса DTO для преобразования результата или null для получения сырых данных
     * @return object|array|null найденная запись в виде объекта, массива или null, если запись не найдена
     */
    public function fetchOneBySql(string $sql, array $params, ?string $dtoClass): object|array|null;
}
