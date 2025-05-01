<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Manager\Contract\DbalFinderInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\DbalMutatorInterface;
use Elrise\Bundle\DbalBundle\Service\Transaction\TransactionServiceInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'dbal:test:transaction-service')]
final class TransactionServiceCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly TransactionServiceInterface $transactionService,
        private readonly DbalMutatorInterface $mutator,
        private readonly DbalFinderInterface $finder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->truncateTable(self::TABLE_NAME);

        $output->writeln('🚀 Тест транзакции с commit...');

        $data = $this->generateNormalRow();
        unset($data['id']);

        $this->transactionService->transactional(function () use ($output, $data) {
            $output->writeln('🟢 Внутри транзакции — commit');
            $this->mutator->insert('test_data_types', $data);

            return true;
        });

        $output->writeln('✅ Транзакция успешно завершена');

        $output->writeln('🔥 Тест транзакции с rollback...');

        try {
            $this->transactionService->transactional(function () use ($output, $data): void {
                $output->writeln('🔴 Внутри транзакции — вызов исключения');
                $this->mutator->insert('test_data_types', $data);
                throw new RuntimeException('Искусственное исключение для rollback');
            });
        } catch (Throwable $e) {
            $output->writeln('🛑 Ожидаемый rollback с сообщением: ' . $e->getMessage());
        }

        $count = $this->finder->count(self::TABLE_NAME);

        if ($count === 1) {
            $output->writeln('🔎 Проверка: в базе осталась 1 запись — ✅ OK' . "\n");
        } else {
            $output->writeln("⚠️ Проверка: в базе ожидалась 1 запись, найдено: $count — ❌ ERROR\n");
        }

        return Command::SUCCESS;
    }

    protected function getTestType(): string
    {
        return 'transactions';
    }
}
