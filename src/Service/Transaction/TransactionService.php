<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Service\Transaction;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\TransactionIsolationLevel;
use RuntimeException;
use Throwable;

final class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        private Connection $connection,
        private TransactionIsolationLevel $defaultIsolationLevel = TransactionIsolationLevel::REPEATABLE_READ,
    ) {
    }

    /**
     * @throws Exception
     */
    public function transactional(Closure $callback, ?TransactionIsolationLevel $isolation = null): mixed
    {
        $this->begin($isolation);

        try {
            $result = $callback();
            $this->commit();

            return $result;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function begin(?TransactionIsolationLevel $isolation = null): void
    {
        $nestingLevel = $this->connection->getTransactionNestingLevel();

        if ($nestingLevel === 0) {
            if ($isolation !== null && $this->connection->getTransactionIsolation() !== $isolation) {
                $this->connection->setTransactionIsolation($isolation);
            }

            $this->connection->beginTransaction();
        } else {
            $this->connection->executeStatement("SAVEPOINT LEVEL{$nestingLevel}");
        }
    }

    /**
     * @throws Exception
     */
    public function commit(): void
    {
        $nestingLevel = $this->connection->getTransactionNestingLevel();

        if ($nestingLevel === 1) {
            $this->connection->commit();

            if ($this->connection->getTransactionIsolation() !== $this->defaultIsolationLevel) {
                $this->connection->setTransactionIsolation($this->defaultIsolationLevel);
            }
        } elseif ($nestingLevel > 1) {
            $this->connection->executeStatement('RELEASE SAVEPOINT LEVEL' . ($nestingLevel - 1));
        } else {
            throw new RuntimeException('Cannot commit: not in transaction');
        }
    }

    /**
     * @throws Exception
     */
    public function rollback(): void
    {
        $nestingLevel = $this->connection->getTransactionNestingLevel();

        if ($nestingLevel === 1) {
            $this->connection->rollBack();

            if ($this->connection->getTransactionIsolation() !== $this->defaultIsolationLevel) {
                $this->connection->setTransactionIsolation($this->defaultIsolationLevel);
            }
        } elseif ($nestingLevel > 1) {
            $this->connection->executeStatement('ROLLBACK TO SAVEPOINT LEVEL' . ($nestingLevel - 1));
        } else {
            throw new RuntimeException('Cannot rollback: not in transaction');
        }
    }

    public function inTransaction(): bool
    {
        return $this->connection->isTransactionActive();
    }
}
