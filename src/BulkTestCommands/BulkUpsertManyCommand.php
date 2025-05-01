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
    description: 'Вставляет N записей, затем обновляет их через upsertMany().',
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
        $output->writeln("🔄 Генерация $this->count записей, вставка 10%, upsert по name, кругов: $this->cycle");

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateBulkRow();
        }

        // 1. Вставка первых 10%
        $insertCount = (int) ceil($this->count * 0.1);
        $insertedRows = array_slice($buffer, 0, $insertCount);
        $this->bulkInserter->insertMany(self::TABLE_NAME, $insertedRows);

        // 2. Получаем реальные ID для вставленных 10%
        $existingRows = $this->getLastInsertedRows($insertCount); // ['id' => ..., 'name' => ...]
        $existingIndex = 0;

        // 3. Прямо в исходном $buffer подставляем id и name для 10-й, 20-й, 30-й и т.д.
        for ($i = 0; $i < $this->count; ++$i) {
            if (($i + 1) % 10 === 0 && isset($existingRows[$existingIndex])) {
                $buffer[$i]['id'] = $existingRows[$existingIndex]['id'];
                $buffer[$i]['name'] = 'updated_' . uniqid();
                ++$existingIndex;
            }
        }

        $output->writeln('✅ Вставка завершена.');

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
            $output->writeln("🔎 Проверка: общее количество записей $totalCount — ✅ OK\n");
        } else {
            $output->writeln("⚠️ Проверка: ожидалось $this->count записей, найдено: $totalCount — ❌ ERROR\n");
        }

        return $result;
    }

    protected function getTestType(): string
    {
        return 'bulk-upsert';
    }
}
