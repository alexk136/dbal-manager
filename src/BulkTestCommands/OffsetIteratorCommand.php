<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Enum\BulkInserterInterface;
use Elrise\Bundle\DbalBundle\Enum\OffsetIteratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dbal:test:offset-iterator',
    description: 'Reads N records from the test_data_types table using offset',
)]
final class OffsetIteratorCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly OffsetIteratorInterface $offsetIterator,
        private readonly BulkInserterInterface $bulkInserter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("📄 Pagination of $this->count records using offset iterator (chunks of $this->chunkSize), iterations: $this->cycle");

        $this->truncateTable(self::TABLE_NAME);
        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $buffer[] = $this->generateBulkRow();
        }
        $this->bulkInserter->insertMany(self::TABLE_NAME, $buffer);

        $this->offsetIterator->setChunkSize($this->chunkSize)->setOrderDirection('ASC');

        $output->writeln('✅ Insertion completed.');

        $sql = 'SELECT * FROM ' . self::TABLE_NAME;

        return $this->runBenchmark(
            function () use ($sql): array {
                $buffer = [];

                foreach ($this->offsetIterator->iterate($sql) as $item) {
                    $buffer[] = $item;

                    if (count($buffer) >= $this->count) {
                        break;
                    }
                }

                return $buffer;
            },
            $output,
            $buffer,
        );
    }

    protected function getTestType(): string
    {
        return 'offset-iterator';
    }
}
