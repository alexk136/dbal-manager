<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Contract;

interface DbalConfigurableExecutorInterface
{
    /**
     * Устанавливает размер чанка для пакетных операций.
     */
    public function setChunkSize(int $chunkSize): static;

    /**
     * Устанавливает список полей для операций записи.
     * Важно использовать с осторожностью, чтобы не нарушить соответствие структурам данных.
     */
    public function setFieldNames(array $fieldNames): static;

    /**
     * Сбрасывает конфигурацию на дефолтные значения из конфига.
     */
    public function resetConfig(): static;
}
