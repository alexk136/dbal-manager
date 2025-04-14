<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

use Generator;

interface OffsetIteratorInterface
{
    /**
     * Итерация по результатам запроса с использованием подхода на основе смещения.
     *
     * @param string $sql SQL-запрос для выполнения.
     * @param array $params Ассоциативный массив параметров для подстановки в запрос.
     * @param array $types Ассоциативный массив типов параметров для запроса.
     * @param string $indexField Название поля, используемого в качестве индекса для итерации.
     * @param string|null $dtoClass Полное имя класса DTO для преобразования результатов или null для получения сырых данных.
     * @return Generator Генератор, возвращающий результаты запроса, каждый из которых представлен объектом или массивом.
     */
    public function iterate(
        string $sql,
        array $params,
        array $types,
        string $indexField,
        ?string $dtoClass,
    ): Generator;
}
