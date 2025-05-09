<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Contract;

use Closure;
use Doctrine\DBAL\TransactionIsolationLevel;

interface TransactionServiceInterface
{
    /**
     * Executes a transaction with the provided callback, using the specified isolation level.
     *
     * @param Closure $callback The callback function to execute within the transaction.
     * @param TransactionIsolationLevel|null $isolation The isolation level for the transaction (optional).
     * @return mixed The result of the callback execution.
     */
    public function transactional(Closure $callback, ?TransactionIsolationLevel $isolation = null): mixed;

    /**
     * Begins a new transaction with an optional isolation level.
     *
     * @param TransactionIsolationLevel|null $isolation The isolation level for the transaction (optional).
     */
    public function begin(?TransactionIsolationLevel $isolation = null): void;

    /**
     * Commits the current transaction, saving all changes made during the transaction.
     */
    public function commit(): void;

    /**
     * Rolls back the current transaction, discarding all changes made during the transaction.
     */
    public function rollback(): void;

    /**
     * Checks if the system is currently in a transaction.
     *
     * @return bool True if inside a transaction, false otherwise.
     */
    public function inTransaction(): bool;
}
