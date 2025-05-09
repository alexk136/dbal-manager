<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

interface DbalConfigurableExecutorInterface
{
    /**
     * Sets the chunk size for batch operations.
     */
    public function setChunkSize(int $chunkSize): static;

    /**
     * Sets the list of fields for write operations.
     * Use with caution to avoid breaking data structure consistency.
     */
    public function setFieldNames(array $fieldNames): static;

    /**
     * Resets the configuration to the default values from the config.
     */
    public function resetConfig(): static;
}
