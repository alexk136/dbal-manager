<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\BulkUpdaterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dbal:test:bulk-update-many',
    description: 'Вставляет N записей и затем обновляет их через updateMany().',
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
        $output->writeln("🔄 Генерация и вставка $this->count записей, затем обновление, кругов: $this->cycle");

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

        $output->writeln('✅ Вставка завершена.');

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
            $output->writeln("🔎 Проверка: обновлено $updatedCount записей — ✅ OK\n");
        } else {
            $output->writeln("⚠️ Проверка: ожидалось обновление $this->count записей, найдено: $updatedCount — ❌ ERROR\n");
        }

        return $result;
    }

    protected function getTestType(): string
    {
        return 'bulk-update';
    }
}
