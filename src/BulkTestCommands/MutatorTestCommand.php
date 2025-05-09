<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Enum\DbalMutatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'dbal:test:mutator')]
final class MutatorTestCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly DbalMutatorInterface $mutator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('🚀 Test Mutator...');

        $this->truncateTable(self::TABLE_NAME);

        $this->testInsert($output);
        $this->testUpdate($output);
        $this->testDelete($output);
        $this->testExecute($output);

        return Command::SUCCESS;
    }

    protected function getTestType(): string
    {
        return 'mutator';
    }

    private function testInsert(OutputInterface $output): void
    {
        $output->writeln('📝 insert');

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output): void {
                $row = $this->generateNormalRow();
                $row['name'] = 'Inserted Name';
                $row['price'] = $this->faker->randomFloat(2, 100, 10000);

                unset($row['id']);

                $this->mutator->insert(self::TABLE_NAME, $row);

                $output->writeln('✅ Insertion completed.');
            })(),
            $output,
            [],
        );
    }

    private function testUpdate(OutputInterface $output): void
    {
        $output->writeln('✏️ update');

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output): void {
                $newName = 'Updated Name';
                $this->mutator->update(self::TABLE_NAME, ['name' => $newName], ['id' => 1]);

                $output->writeln('✅ Update completed.');
            })(),
            $output,
            [],
        );
    }

    private function testDelete(OutputInterface $output): void
    {
        $output->writeln('🗑 delete');

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output): void {
                $this->mutator->delete(self::TABLE_NAME, ['id' => 1]);

                $output->writeln('✅ Deletion completed.');
            })(),
            $output,
            [],
        );
    }

    private function testExecute(OutputInterface $output): void
    {
        $output->writeln('🛠 execute');

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output): void {
                $sql = 'UPDATE ' . self::TABLE_NAME . ' SET name = :name WHERE id > :id';
                $affectedRows = $this->mutator->execute($sql, [
                    'name' => 'Executed Update',
                    'id' => 1,
                ]);

                $output->writeln("✅ Completed. Rows affected: $affectedRows");
            })(),
            $output,
            [],
        );
    }
}
