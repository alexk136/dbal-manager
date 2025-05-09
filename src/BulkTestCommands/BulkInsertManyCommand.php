<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Enum\BulkInserterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dbal:test:bulk-insert-many',
    description: 'Inserts N records into the test_data_types table using insertMany().',
)]
final class BulkInsertManyCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly BulkInserterInterface $bulkInserter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("🔄 Inserting $this->count records via bulk insert (chunks of $this->chunkSize), insert iterations: $this->cycle");

        $this->truncateTable(self::TABLE_NAME);

        $result = $this->runBenchmark(
            fn (array $buffer) => $this->bulkInserter->setChunkSize($this->chunkSize)->insertMany(self::TABLE_NAME, $buffer),
            $output,
        );

        $count = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from(self::TABLE_NAME)
            ->executeQuery()->fetchOne();

        if ($count === $this->count) {
            $output->writeln("🔎 Verification: $count records inserted — ✅ OK\n");
        } else {
            $output->writeln("⚠️ Verification: expected $this->count records, found: $count — ❌ ERROR\n");
        }

        return $result;
    }

    protected function getTestType(): string
    {
        return 'bulk-insert';
    }
}
