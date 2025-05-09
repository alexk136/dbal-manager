<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Enum\BulkInserterInterface;
use Elrise\Bundle\DbalBundle\Enum\BulkUpserterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dbal:test:bulk-upsert-many',
    description: 'Inserts N records, then updates them using upsertMany().',
)]
final class BulkUpsertManyCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly BulkInserterInterface $bulkInserter,
        private readonly BulkUpserterInterface $bulkUpserter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("🔄 Generating $this->count records, inserting 10%, upserting by name, iterations: $this->cycle");

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateBulkRow();
        }

        $insertCount = (int) ceil($this->count * 0.1);
        $insertedRows = array_slice($buffer, 0, $insertCount);
        $this->bulkInserter->insertMany(self::TABLE_NAME, $insertedRows);

        $existingRows = $this->getLastInsertedRows($insertCount);
        $existingIndex = 0;

        for ($i = 0; $i < $this->count; ++$i) {
            if (($i + 1) % 10 === 0 && isset($existingRows[$existingIndex])) {
                $buffer[$i]['id'] = $existingRows[$existingIndex]['id'];
                $buffer[$i]['name'] = 'updated_' . uniqid();
                ++$existingIndex;
            }
        }

        $output->writeln('✅ Insertion completed.');

        $result = $this->runBenchmark(
            fn (array $unused) => $this->bulkUpserter
                ->upsertMany(
                    self::TABLE_NAME,
                    $buffer,
                    ['id'],
                ),
            $output,
            $buffer,
        );

        $totalCount = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from(self::TABLE_NAME)
            ->where('deleted_at IS NOT NULL')
            ->executeQuery()->fetchOne();

        if ($totalCount === $this->count) {
            $output->writeln("🔎 Verification: total record count is $totalCount — ✅ OK\n");
        } else {
            $output->writeln("⚠️ Verification: expected $this->count records, found: $totalCount — ❌ ERROR\n");
        }

        return $result;
    }

    protected function getTestType(): string
    {
        return 'bulk-upsert';
    }
}
