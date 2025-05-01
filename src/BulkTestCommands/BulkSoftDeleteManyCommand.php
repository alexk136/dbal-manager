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
    name: 'dbal:test:bulk-soft-delete-many',
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

        $this->truncateTable(self::TABLE_NAME);

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateBulkRow();
        }

        $this->bulkInserter->insertMany(self::TABLE_NAME, $buffer);

        $idsToDelete = $this->getLastInsertedIds($this->count);

        $output->writeln('✅ Вставка завершена.');

        $result = $this->runBenchmark(
            fn (array $unused) => $this->bulkDeleter->setChunkSize($this->chunkSize)->deleteSoftMany(self::TABLE_NAME, $idsToDelete),
            $output,
            $buffer,
        );

        $deletedCount = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from(self::TABLE_NAME)
            ->where('deleted_at IS NOT NULL')
            ->executeQuery()->fetchOne();

        if ($deletedCount === $this->count) {
            $output->writeln("🔎 Проверка: всем записям установлен deleted_at — ✅ OK\n");
        } else {
            $output->writeln("⚠️ Проверка: ожидалось $this->count записей с deleted_at, найдено: $deletedCount — ❌ ERROR\n");
        }

        return $result;
    }

    protected function getTestType(): string
    {
        return 'bulk-soft-delete';
    }
}
