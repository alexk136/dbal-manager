<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

use Generator;

interface CursorIteratorInterface
{
    /**
     * Итерация по результатам запроса с использованием подхода на основе курсора.
     *
     * @param string $tableName Название таблицы, из которой выполняется запрос.
     * @param string $cursorField Название поля, используемого в качестве курсора для итерации.
     * @param array $initialCursorValues Ассоциативный массив начальных значений курсора для старта итерации.
     * @param string|null $dtoClass Полное имя класса DTO для преобразования результатов или null для получения сырых данных.
     * @return Generator Генератор, возвращающий результаты запроса, каждый из которых представлен объектом или массивом.
     */
    public function iterate(
        string $tableName,
        string $cursorField,
        array $initialCursorValues,
        ?string $dtoClass,
    ): Generator;
}
