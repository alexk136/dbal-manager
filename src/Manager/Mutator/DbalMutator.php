<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Mutator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Elrise\Bundle\DbalBundle\Enum\DbalMutatorInterface;

final readonly class DbalMutator implements DbalMutatorInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @throws Exception
     */
    public function insert(string $table, array $data): void
    {
        $this->connection->insert($table, $data);
    }

    /**
     * @throws Exception
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->connection->executeStatement($sql, $params);
    }

    /**
     * @throws Exception
     */
    public function update(string $table, array $data, array $criteria): void
    {
        $this->connection->update($table, $data, $criteria);
    }

    /**
     * @throws Exception
     */
    public function delete(string $table, array $criteria): void
    {
        $this->connection->delete($table, $criteria);
    }
}
