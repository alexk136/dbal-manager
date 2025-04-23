<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkDeleterInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test:bulk-delete-many',
    description: 'Удаляет N записей из таблицы test_data_types через deleteMany().',
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
        $output->writeln("🗑️ Удаление $this->count записей через bulk delete (чанки по $this->chunkSize), кругов: $this->cycle");

        $this->truncateTable(self::TABLE_NAME);

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateRow();
        }

        $this->bulkInserter->insertMany(self::TABLE_NAME, $buffer);

        $idsToDelete = $this->getLastInsertedIds($this->count);

        return $this->runBenchmark(
            fn (array $unused) => $this->bulkDeleter->setChunkSize($this->chunkSize)->deleteMany(self::TABLE_NAME, $idsToDelete),
            $output,
        );
    }

    protected function getTestType(): string
    {
        return 'bulk-delete';
    }
}
