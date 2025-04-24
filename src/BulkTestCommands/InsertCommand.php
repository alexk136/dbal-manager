<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Manager\Contract\DbalMutatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test:insert',
    description: 'Вставляет N записей в таблицу test_data_types и логирует производительность.',
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
        $output->writeln("🔄 Вставка {$this->count} записей в таблицу `test_data_types`...");

        return $this->runBenchmark(
            fn (array $buffer) => array_map(
                function ($value) {
                    unset($value['id']);

                    return $this->mutator->insert(self::TABLE_NAME, $value);
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
