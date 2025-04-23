<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkUpdaterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test:bulk-update-many',
    description: 'Вставляет N записей и затем обновляет их через updateMany().',
)]
class BulkUpdateManyCommand extends AbstractTestCommand
{
    public function __construct(
        private readonly BulkInserterInterface $bulkInserter,
        private readonly BulkUpdaterInterface $bulkUpdater,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("🔄 Генерация и вставка $this->count записей, затем обновление, кругов: $this->cycle");

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateRow();
        }

        $this->bulkInserter->insertMany('test_data_types', $buffer);

        $ids = $this->getLastInsertedIds($this->count);

        $buffer = array_map(static fn (int $id) => [
            'id' => $id,
            'name' => 'updated_' . uniqid(),
        ], $ids);

        return $this->runBenchmark(
            fn (array $unused) => $this->bulkUpdater
                ->updateMany(
                    'test_data_types',
                    $buffer,
                    ['id'],
                ),
            $output,
        );
    }

    protected function getTestType(): string
    {
        return 'bulk-update';
    }

    private function getLastInsertedIds(int $limit): array
    {
        return $this->connection
            ->executeQuery("SELECT id FROM test_data_types ORDER BY id DESC LIMIT $limit")
            ->fetchFirstColumn();
    }
}
