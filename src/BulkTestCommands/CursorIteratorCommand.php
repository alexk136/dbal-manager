<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Enum\BulkInserterInterface;
use Elrise\Bundle\DbalBundle\Enum\CursorIteratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dbal:test:cursor-iterator',
    description: 'Reads N records from the test_data_types table using a cursor iterator.',
)]
final class CursorIteratorCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly CursorIteratorInterface $cursorIterator,
        private readonly BulkInserterInterface $bulkInserter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("📥 Reading $this->count records using cursor iterator (chunks of $this->chunkSize), iterations: $this->cycle");

        $this->truncateTable(self::TABLE_NAME);

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateBulkRow();
        }

        $this->bulkInserter->insertMany(self::TABLE_NAME, $buffer);

        $this->cursorIterator->setChunkSize($this->chunkSize)->setOrderDirection('ASC');

        $output->writeln('✅ Insertion completed.');

        return $this->runBenchmark(
            function (): array {
                $buffer = [];

                foreach ($this->cursorIterator->iterate(self::TABLE_NAME) as $item) {
                    $buffer[] = $item;

                    if (count($buffer) >= $this->count) {
                        break;
                    }
                }

                return $buffer;
            },
            $output,
        );
    }

    protected function getTestType(): string
    {
        return 'cursor-iterator';
    }
}
