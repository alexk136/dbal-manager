<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Enum\DbalMutatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dbal:test:insert',
    description: 'Inserts N records into the test_data_types table and logs performance metrics.',
)]
final class InsertCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly DbalMutatorInterface $mutator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("🔄 Inserting $this->count records into the table `test_data_types`...");

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $row = $this->generateNormalRow();
            $buffer[] = array_merge($row, [
                'name' => "Benchmark-$i",
                'price' => $this->faker->randomFloat(2, 100, 10000),
            ]);
        }

        return $this->runBenchmark(
            fn (array $unused) => array_map(
                function ($value): void {
                    unset($value['id']);

                    $this->mutator->insert(self::TABLE_NAME, $value);
                },
                $buffer,
            ),
            $output,
        );
    }

    protected function getTestType(): string
    {
        return 'insert';
    }
}
