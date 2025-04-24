<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkUpserterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test:bulk-upsert-many',
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
            $buffer[] = $this->generateRow();
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

        return $this->runBenchmark(
            fn (array $unused) => $this->bulkUpserter
                ->upsertMany(
                    'test_data_types',
                    $buffer,
                    ['id', 'name'],
                ),
            $output,
            $buffer,
        );
    }

    protected function getTestType(): string
    {
        return 'bulk-upsert';
    }
}
