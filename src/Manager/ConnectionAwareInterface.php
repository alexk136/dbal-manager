<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\DBAL\DbalConnection;

interface ConnectionAwareInterface
{
    public function getConnection(): Connection|DbalConnection;
}
