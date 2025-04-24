<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\BulkTestCommands;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Faker\Factory;
use Faker\Generator;
use ITech\Bundle\DbalBundle\Manager\Contract\IdStrategy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractTestCommand extends Command
{
    protected const string TABLE_NAME = 'test_data_types';

    protected string $csvPath;
    protected bool $track;
    protected int $count;
    protected int $cycle;
    protected int $chunkSize;
    protected float $globalStart;
    protected float $globalMemStart;
    protected Generator $faker;
    protected float $totalElapsed = 0;
    protected float $peakMemory = 0;
    protected Connection $connection;

    abstract protected function getTestType(): string;

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_OPTIONAL, 'Количество записей для вставки', 1)
            ->addOption('track', null, InputOption::VALUE_OPTIONAL, 'Включить логирование производительности', 0)
            ->addOption('cycle', null, InputOption::VALUE_OPTIONAL, 'Количество кругов прогона теста', 1)
            ->addOption('chunk', null, InputOption::VALUE_OPTIONAL, 'Размер чанка для пакетной вставки', 1000);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->faker = Factory::create();

        $this->count = (int) $input->getOption('count');
        $this->track = (bool) $input->getOption('track');
        $this->cycle = (int) $input->getOption('cycle');
        $this->chunkSize = (int) $input->getOption('chunk');

        $testType = $this->getTestType();
        $this->csvPath = sprintf('var/log/%s_%d.csv', $testType, $this->count);

        if ($this->track) {
            file_put_contents($this->csvPath, "index,time_sec,memory_mb,memory_delta_mb,total_elapsed_sec\n");
        }

        $this->globalStart = microtime(true);
        $this->globalMemStart = memory_get_usage(true);
    }

    protected function finalize(OutputInterface $output, float $totalElapsed, float $peakMemory, float $avgTime): void
    {
        $output->writeln(sprintf('⏱ Суммарное время из шагов: %.6f сек', $totalElapsed));
        $output->writeln(sprintf('📦 Пиковое использование памяти: %.6f МБ', $peakMemory));
        $output->writeln(sprintf('⚙️ Среднее время на вставку: %.6f сек', $avgTime));
        $output->writeln("\n");

        if ($this->track) {
            file_put_contents(
                $this->csvPath,
                "\n# Summary\nTOTAL_ELAPSED,{$this->totalElapsed},PEAK_MEMORY,{$this->peakMemory},AVG_INSERT,{$avgTime}\n",
                FILE_APPEND,
            );
            $output->writeln("📄 Детали сохранены в файл: <info>{$this->csvPath}</info>");
        }
    }

    protected function benchmarkStep(int $index, float $duration, float $memUsed, float $memDelta, float $totalElapsed, float $peakMemory): void
    {
        $totalElapsed += $duration;
        $this->totalElapsed += $duration;
        $this->peakMemory = max($this->peakMemory, $memUsed);

        if ($this->track) {
            file_put_contents(
                $this->csvPath,
                sprintf("%d,%.6f,%.6f,%.6f,%.6f\n", $index + 1, $duration, $memUsed, $memDelta, $totalElapsed),
                FILE_APPEND,
            );
        }
    }

    protected function runBenchmark(callable $operation, OutputInterface $output, ?array $preGeneratedBuffer = null): int
    {
        $totalElapsed = 0;
        $totalMemory = 0;

        for ($i = 0; $i < $this->cycle; ++$i) {
            $buffer = $preGeneratedBuffer ?? array_map(fn () => $this->generateRow(), range(1, $this->count));

            gc_collect_cycles();

            $start = microtime(true);
            $memBefore = memory_get_usage() / 1024 / 1024;

            $operation($buffer);

            $duration = microtime(true) - $start;
            $totalElapsed += $duration;

            $memAfter = memory_get_usage() / 1024 / 1024;
            $memDelta = $memAfter - $memBefore;
            $totalMemory += $memDelta;

            unset($buffer);
            gc_collect_cycles();

            if ($this->track) {
                file_put_contents(
                    $this->csvPath,
                    sprintf("%d,%.6f,%.6f,%.6f,%.6f\n", $i + 1, $duration, $totalMemory, $memDelta, $totalElapsed),
                    FILE_APPEND,
                );
            }
        }

        $this->finalize($output, $totalElapsed, $totalMemory, $totalElapsed / $this->cycle);

        return Command::SUCCESS;
    }

    protected function generateRow(): array
    {
        return [
            'id' => IdStrategy::AUTO_INCREMENT,
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 1, 10000),
            'active' => (int) $this->faker->boolean(),
            'meta' => json_encode([
                'ip' => $this->faker->ipv4,
                'user_agent' => $this->faker->userAgent,
                'tags' => $this->faker->words(3),
            ]),
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'status' => $this->faker->randomElement(['new', 'processing', 'done']),
            'data_blob' => random_bytes(32),
        ];
    }

    protected function truncateTable(string $tableName): void
    {
        $this->connection->executeStatement(sprintf('TRUNCATE TABLE `%s`', $tableName));
    }

    protected function getLastInsertedIds(int $limit): array
    {
        return $this->connection
            ->executeQuery('SELECT id FROM ' . self::TABLE_NAME . ' ORDER BY id DESC LIMIT :limit',
                ['limit' => $limit],
                ['limit' => ParameterType::INTEGER],
            )
            ->fetchFirstColumn();
    }

    protected function getLastInsertedRows(int $limit): array
    {
        return $this->connection
            ->executeQuery('SELECT id, name FROM ' . self::TABLE_NAME . ' ORDER BY id DESC LIMIT :limit',
                ['limit' => $limit],
                ['limit' => ParameterType::INTEGER],
            )
            ->fetchAllAssociative();
    }
}
