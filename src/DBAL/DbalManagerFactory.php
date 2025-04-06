<?php

namespace ITech\Bundle\DbalBundle\DBAL;

use ITech\Bundle\DbalBundle\Manager\DbalManager;
use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Utils\DtoDeserializerInterface;

final readonly class DbalManagerFactory
{
    public function __construct(
        private DtoDeserializerInterface $dtoDeserializer
    ) {
    }

    public function createByConnection(Connection $connection): DbalManager
    {
        return new DbalManager(
            $connection,
            $this->dtoDeserializer
        );
    }
}