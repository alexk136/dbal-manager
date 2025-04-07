<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Service\Dto\DtoFieldExtractorInterface;
use ITech\Bundle\DbalBundle\Utils\DtoDeserializerInterface;

final readonly class DbalManagerFactory
{
    public function __construct(
        private DtoDeserializerInterface $dtoDeserializer,
        private DtoFieldExtractorInterface $dtoFieldExtractor,
        private DbalBundleConfig $config,
    ) {
    }

    public function createByConnection(Connection $connection): DbalManager
    {
        return new DbalManager(
            $connection,
            $this->dtoDeserializer,
            $this->dtoFieldExtractor,
            $this->config ?? new DbalBundleConfig(),
        );
    }
}
