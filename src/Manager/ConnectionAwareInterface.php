<?php

declare(strict_types=1);

namespace Itech\Bundle\DbalBundle\Manager;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\DBAL\DbalConnection;

interface ConnectionAwareInterface
{
    public function getConnection(): Connection|DbalConnection;
}
