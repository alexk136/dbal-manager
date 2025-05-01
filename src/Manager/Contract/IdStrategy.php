<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

enum IdStrategy
{
    case AUTO_INCREMENT;
    case UID;
    case UUID;
    case INT;
    case STRING;
    case DEFAULT;
}
