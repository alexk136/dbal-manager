<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Enum\BulkInserterInterface;
use Elrise\Bundle\DbalBundle\Enum\BulkUpdaterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dbal:test:bulk-update-many',
    description: 'Inserts N records and then updates them using updateMany().',
)]
final class BulkUpdateManyCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly BulkInserterInterface $bulkInserter,
        private readonly BulkUpdaterInterface $bulkUpdater,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("🔄 Generating and inserting $this->count records, then updating them, iterations: $this->cycle");

        $this->truncateTable(self::TABLE_NAME);

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateBulkRow();
        }

        $this->bulkInserter->insertMany(self::TABLE_NAME, $buffer);

        $ids = $this->getLastInsertedIds($this->count);

        $buffer = array_map(static fn (int $id) => [
            'id' => $id,
            'name' => 'updated_' . uniqid(),
        ], $ids);

        $output->writeln('✅ Insertion completed.');

        $result = $this->runBenchmark(
            fn (array $unused) => $this->bulkUpdater
                ->updateMany(
                    self::TABLE_NAME,
                    $buffer,
                    ['id'],
                ),
            $output,
            $buffer,
        );

        $updatedCount = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from(self::TABLE_NAME)
            ->where('name LIKE :pattern')
            ->setParameter('pattern', 'updated_%')
            ->executeQuery()
            ->fetchOne();

        if ((int) $updatedCount === $this->count) {
            $output->writeln("🔎 Verification: $updatedCount records updated — ✅ OK\n");
        } else {
            $output->writeln("⚠️ Verification: expected $this->count records to be updated, found: $updatedCount — ❌ ERROR\n");
        }

        return $result;
    }

    protected function getTestType(): string
    {
        return 'bulk-update';
    }
}
