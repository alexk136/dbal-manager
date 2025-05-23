<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Sql\Builder;

enum UpsertReplaceType: string
{
    case Increment = 'increment';
    case Decrement = 'decrement';
    case Condition = 'condition';
}
