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
    name: 'app:test:bulk-soft-delete-many',
    description: 'Мягко удаляет N записей из таблицы test_data_types через deleteSoftMany().',
)]
final class BulkSoftDeleteManyCommand extends AbstractTestCommand
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
        $output->writeln("🔄 Soft delete $this->count записей через deleteSoftMany() (чанки по $this->chunkSize), кругов: $this->cycle");

        // 1. Генерация и вставка записей
        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateRow();
        }

        $this->bulkInserter->insertMany('test_data_types', $buffer);

        // 2. Получение ID для мягкого удаления
        $idsToDelete = $this->getLastInsertedIds($this->count);

        // 3. Benchmark на soft delete
        return $this->runBenchmark(
            fn (array $unused) => $this->bulkDeleter
                ->setChunkSize($this->chunkSize)
                ->deleteSoftMany('test_data_types', $idsToDelete),
            $output,
        );
    }

    protected function getTestType(): string
    {
        return 'bulk-soft-delete';
    }
}
