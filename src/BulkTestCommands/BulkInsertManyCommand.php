<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test:bulk-insert-many',
    description: 'Вставляет N записей в таблицу test_data_types через insertMany().',
)]
final class BulkInsertManyCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly BulkInserterInterface $bulkInserter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("🔄 Вставка $this->count записей через bulk insert (чанки по $this->chunkSize), кругов вставки: $this->cycle");

        return $this->runBenchmark(
            fn (array $buffer) => $this->bulkInserter->setChunkSize($this->chunkSize)->insertMany(self::TABLE_NAME, $buffer),
            $output,
        );
    }

    protected function getTestType(): string
    {
        return 'bulk-insert';
    }
}
