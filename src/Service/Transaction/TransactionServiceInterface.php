<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Service\Transaction;

use Closure;
use Doctrine\DBAL\TransactionIsolationLevel;

interface TransactionServiceInterface
{
    public function transactional(Closure $callback, ?TransactionIsolationLevel $isolation = null): mixed;

    public function begin(?TransactionIsolationLevel $isolation = null): void;

    public function commit(): void;

    public function rollback(): void;

    public function inTransaction(): bool;
}
