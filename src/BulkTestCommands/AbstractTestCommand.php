<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL120Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Elrise\Bundle\DbalBundle\DBAL\DbalParameterType;
use Elrise\Bundle\DbalBundle\Enum\IdStrategy;
use Faker\Factory;
use Faker\Generator;
use InvalidArgumentException;
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
    protected string $cachedPlatform;

    abstract protected function getTestType(): string;

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_OPTIONAL, 'Number of records to insert', 1)
            ->addOption('track', null, InputOption::VALUE_OPTIONAL, 'Enable performance logging', 0)
            ->addOption('cycle', null, InputOption::VALUE_OPTIONAL, 'Number of test run iterations.', 1)
            ->addOption('chunk', null, InputOption::VALUE_OPTIONAL, 'Batch insert chunk size', 1000);
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

        if (!isset($this->cachedPlatform)) {
            $platform = $this->connection->getDatabasePlatform();

            $this->cachedPlatform = match ($platform::class) {
                MySQLPlatform::class => 'mysql',
                PostgreSQLPlatform::class, PostgreSQL120Platform::class => 'pgsql',
                default => throw new InvalidArgumentException('Unsupported platform: ' . get_class($platform)),
            };
        }
    }

    protected function finalize(OutputInterface $output, float $totalElapsed, float $peakMemory, float $avgTime): void
    {
        $output->writeln(sprintf('⏱ Total time from steps: %.6f seconds', $totalElapsed));
        $output->writeln(sprintf('📦 Peak memory usage: %.6f MB', $peakMemory));
        $output->writeln(sprintf('⚙️ Average insert time: %.6f seconds', $avgTime));
        $output->writeln("\n");

        if ($this->track) {
            file_put_contents(
                $this->csvPath,
                "\n# Summary\nTOTAL_ELAPSED,{$this->totalElapsed},PEAK_MEMORY,{$this->peakMemory},AVG_INSERT,{$avgTime}\n",
                FILE_APPEND,
            );
            $output->writeln("📄 Details saved to file: <info>{$this->csvPath}</info>");
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
            $buffer = $preGeneratedBuffer ?? array_map(fn () => $this->generateBulkRow(), range(1, $this->count));

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
                    sprintf('%d,%.6f,%.6f,%.6f,%.6f', $i + 1, $duration, $totalMemory, $memDelta, $totalElapsed),
                    FILE_APPEND,
                );
            }
        }

        $this->finalize($output, $totalElapsed, $totalMemory, $totalElapsed / $this->cycle);

        return Command::SUCCESS;
    }

    protected function generateNormalRow(): array
    {
        $row = $this->generateBulkRow();

        if ($this->cachedPlatform === 'pgsql') {
            $row = array_merge($row, [
                'coordinates' => '{' . implode(',', array_map(
                    fn () => $this->faker->randomFloat(6, -1000, 1000),
                    range(1, 5),
                )) . '}',
                'float4_coordinates' => '{' . implode(',', array_map(
                    fn () => (float) number_format($this->faker->randomFloat(6, -1000, 1000), 6, '.', ''),
                    range(1, 5),
                )) . '}',
            ]);
        }

        return $row;
    }

    protected function generateBulkRow(): array
    {
        $row = [
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
        ];

        if ($this->cachedPlatform === 'mysql') {
            $row += [
                'data_blob' => random_bytes(32),
            ];
        }

        if ($this->cachedPlatform === 'pgsql') {
            $row += [
                'data_bytea' => '\\x' . bin2hex(random_bytes(32)),
                'big_value' => $this->faker->numberBetween(100000, 999999999),
                'float_value' => $this->faker->randomFloat(8, 0, 1000),
                'small_value' => $this->faker->numberBetween(1, 32767),
                'text_field' => $this->faker->text(500),
                'inet_field' => $this->faker->ipv4,
                'mac_field' => $this->faker->macAddress,
                'point_field' => sprintf('(%f,%f)', $this->faker->randomFloat(6, -180, 180), $this->faker->randomFloat(6, -90, 90)),
                'interval_field' => sprintf('%d days', $this->faker->numberBetween(1, 365)),
                'json_field' => json_encode([
                    'key' => $this->faker->word,
                    'value' => $this->faker->sentence,
                ]),
                'coordinates' => [
                    array_map(
                        fn () => $this->faker->randomFloat(6, -1000, 1000),
                        range(1, 5),
                    ),
                    DbalParameterType::FLOAT_ARRAY,
                ],
                'float4_coordinates' => [
                    array_map(
                        fn () => (float) number_format($this->faker->randomFloat(6, -1000, 1000), 6, '.', ''),
                        range(1, 5),
                    ),
                    DbalParameterType::FLOAT_ARRAY,
                ],
            ];
        }

        return $row;
    }

    protected function truncateTable(string $tableName): void
    {
        $quotedTable = $this->connection->quoteIdentifier($tableName);
        $this->connection->executeStatement(sprintf('TRUNCATE TABLE %s', $quotedTable));
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
