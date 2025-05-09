<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Enum\DbalFinderInterface;
use Elrise\Bundle\DbalBundle\Enum\DbalMutatorInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\TransactionServiceInterface;
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

        $output->writeln('🚀 Transaction test with commit...');

        $data = $this->generateNormalRow();
        unset($data['id']);

        $this->transactionService->transactional(function () use ($output, $data) {
            $output->writeln('🟢 Inside transaction — commit');
            $this->mutator->insert('test_data_types', $data);

            return true;
        });

        $output->writeln('✅ Transaction completed successfully');
        $output->writeln('🔥 Transaction test with rollback...');

        try {
            $this->transactionService->transactional(function () use ($output, $data): void {
                $output->writeln('🔴 Inside transaction — exception thrown');
                $this->mutator->insert('test_data_types', $data);
                throw new RuntimeException('Artificial exception for rollback');
            });
        } catch (Throwable $e) {
            $output->writeln('🛑 Expected rollback with message: ' . $e->getMessage());
        }

        $count = $this->finder->count(self::TABLE_NAME);

        if ($count === 1) {
            $output->writeln('🔎 Verification: 1 record remains in the database — ✅ OK' . "\n");
        } else {
            $output->writeln("⚠️ Verification: expected 1 record in the database, found: \$count — ❌ ERROR\n");
        }

        return Command::SUCCESS;
    }

    protected function getTestType(): string
    {
        return 'transactions';
    }
}
