<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Placeholder;

use InvalidArgumentException;

interface PlaceholderStrategyInterface
{
    public function formatValue(mixed $value): string;

    /**
     * Формирует плоский список параметров и типов для пакетной вставки/обновления.
     *
     * @param array $batchRows набор строк для обработки (массив массивов значений)
     * @param array|null $whereFields список полей для условия WHERE (если требуется)
     * @param string|null $platform платформа БД для сериализации массивов
     *
     * @return array{0: array<int, mixed>, 1: array<int, int>} массив параметров и соответствующих типов
     *
     * @throws InvalidArgumentException если $batchRows пустой
     */
    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null, ?string $platform = null): array;
}
