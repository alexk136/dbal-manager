<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

use Generator;

interface OffsetIteratorInterface
{
    /**
     * Итерация по результатам запроса с использованием подхода на основе смещения.
     *
     * @param string $sql SQL-запрос для выполнения
     * @param array $params ассоциативный массив параметров для подстановки в запрос
     * @param array $types ассоциативный массив типов параметров для запроса
     * @param string $indexField название поля, используемого в качестве индекса для итерации
     * @param string|null $dtoClass полное имя класса DTO для преобразования результатов или null для получения сырых данных
     * @return Generator генератор, возвращающий результаты запроса, каждый из которых представлен объектом или массивом
     */
    public function iterate(
        string $sql,
        array $params,
        array $types,
        string $indexField,
        ?string $dtoClass,
    ): Generator;
}
