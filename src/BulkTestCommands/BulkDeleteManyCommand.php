<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Enum\BulkDeleterInterface;
use Elrise\Bundle\DbalBundle\Enum\BulkInserterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dbal:test:bulk-delete-many',
    description: 'Deletes N records from the test_data_types table using deleteMany().',
)]
final class BulkDeleteManyCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly BulkDeleterInterface $bulkDeleter,
        private readonly BulkInserterInterface $bulkInserter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("🗑️ Deleting $this->count records via bulk delete (chunks of $this->chunkSize), iterations: $this->cycle");

        $this->truncateTable(self::TABLE_NAME);

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateBulkRow();
        }

        $this->bulkInserter->insertMany(self::TABLE_NAME, $buffer);

        $idsToDelete = $this->getLastInsertedIds($this->count);

        $output->writeln('✅ Insertion completed.');

        $result = $this->runBenchmark(
            fn (array $unused) => $this->bulkDeleter->setChunkSize($this->chunkSize)->deleteMany(self::TABLE_NAME, $idsToDelete),
            $output,
            $buffer,
        );

        $count = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from(self::TABLE_NAME)
            ->executeQuery()->fetchOne();

        if ($count === 0) {
            $output->writeln("🔎 Verification: 0 records remain in the database — ✅ OK\n");
        } else {
            $output->writeln("⚠️ Verification: records remain in the database: $count — ❌ ERROR\n");
        }

        return $result;
    }

    protected function getTestType(): string
    {
        return 'bulk-delete';
    }
}
