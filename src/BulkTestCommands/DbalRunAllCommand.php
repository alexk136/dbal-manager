<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dbal:test:run-all',
)]
final class DbalRunAllCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = [
            '--count' => $input->getOption('count'),
            '--track' => $input->getOption('track'),
            '--cycle' => $input->getOption('cycle'),
            '--chunk' => $input->getOption('chunk'),
        ];

        $commands = [
            'dbal:test:bulk-insert-many',
            'dbal:test:bulk-update-many',
            'dbal:test:bulk-upsert-many',
            'dbal:test:bulk-delete-many',
            'dbal:test:bulk-soft-delete-many',
            'dbal:test:cursor-iterator',
            'dbal:test:offset-iterator',
            'dbal:test:finder',
            'dbal:test:mutator',
            'dbal:test:transaction-service',
            'dbal:test:insert',
        ];

        $application = $this->getApplication();

        if (!$application) {
            throw new LogicException('Application instance not available');
        }

        foreach ($commands as $commandName) {
            $command = $application->find($commandName);
            $commandInput = new ArrayInput($options);
            $output->writeln("<info>Running:</info> $commandName");
            $command->run($commandInput, $output);
        }

        return Command::SUCCESS;
    }

    protected function getTestType(): string
    {
        return 'run-all';
    }
}
