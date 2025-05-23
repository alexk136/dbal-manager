<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Iterator;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Config\DbalBundleConfig;
use Elrise\Bundle\DbalBundle\Service\Serialize\DtoDeserializerInterface;

abstract class AbstractConfigurableIterator
{
    protected int $chunkSize;
    protected string $orderDirection;

    public function __construct(
        protected readonly Connection $connection,
        protected readonly DtoDeserializerInterface $deserializer,
        protected readonly DbalBundleConfig $config,
    ) {
        $this->resetConfig();
    }

    public function setChunkSize(int $chunkSize): static
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    public function setOrderDirection(string $orderDirection): static
    {
        $this->orderDirection = $orderDirection;

        return $this;
    }

    public function resetConfig(): static
    {
        $this->chunkSize = $this->config->chunkSize;
        $this->orderDirection = $this->config->orderDirection;

        return $this;
    }
}
