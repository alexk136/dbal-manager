<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\BulkTestCommands;

use Doctrine\DBAL\Connection;
use Elrise\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\DbalFinderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'dbal:test:finder')]
final class FinderTestCommand extends AbstractTestCommand
{
    public function __construct(
        protected Connection $connection,
        private readonly DbalFinderInterface $finder,
        private readonly BulkInserterInterface $bulkInserter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('🚀 Тест Finder с WHERE и LIMIT...');

        $this->truncateTable(self::TABLE_NAME);

        $buffer = [];

        for ($i = 0; $i < $this->count; ++$i) {
            $row = $this->generateBulkRow();
            $buffer[] = array_merge($row, [
                'name' => "Benchmark-$i",
                'price' => $this->faker->randomFloat(2, 100, 10000),
            ]);
        }

        $this->bulkInserter->insertMany(self::TABLE_NAME, $buffer);

        $this->testFindAll($output);
        $this->testFindOne($output, $buffer);
        $this->testFindById($output);
        $this->testFindByIdList($output, $buffer);
        $this->testFetchAllBySql($output, $buffer);
        $this->testFetchOneBySql($output, $buffer);

        return Command::SUCCESS;
    }

    protected function getTestType(): string
    {
        return 'finder';
    }

    private function testFindAll(OutputInterface $output): void
    {
        $output->writeln('🔍 findAll (WHERE price > 10, LIMIT 2)');

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output): void {
                $qb = $this->connection->createQueryBuilder()
                    ->select('*')
                    ->from(self::TABLE_NAME)
                    ->where('price > :min')
                    ->setParameter('min', 10)
                    ->setMaxResults($this->count);

                $rows = iterator_to_array($this->finder->findAll($qb));
                $output->writeln('➡️ Найдено: ' . count($rows));
            })(),
            $output,
            [],
        );
    }

    private function testFindOne(OutputInterface $output, array $buffer): void
    {
        $output->writeln('🔍 findOne (WHERE price = ' . $buffer[0]['price']);

        $price = $buffer[0]['price'] ?? 100;

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output, $price): void {
                $qb = $this->connection->createQueryBuilder()
                    ->select('*')
                    ->from(self::TABLE_NAME)
                    ->where('price = :price')
                    ->setParameter('price', $price);

                $row = $this->finder->findOne($qb);

                $output->writeln($row
                    ? '✅ Найдена запись: price = ' . $row['price']
                    : '❌ Запись не найдена');
            })(),
            $output,
            [],
        );
    }

    private function testFindById(OutputInterface $output): void
    {
        $id = $this->faker->randomNumber(2);

        $output->writeln('🔍 findById (id = ' . $id . ')');

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output, $id): void {
                $row = $this->finder->findById($id, self::TABLE_NAME);

                $output->writeln($row
                    ? '✅ Найдена запись: ' . json_encode($row)
                    : '❌ Запись не найдена');
            })(),
            $output,
            [],
        );
    }

    private function testFindByIdList(OutputInterface $output, array $buffer): void
    {
        $idList = $this->faker->randomElements(range(1, 100), 3);

        $output->writeln('🔍 findByIdList (' . implode(', ', $idList) . ')');

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output, $idList): void {
                $rows = iterator_to_array($this->finder->findByIdList($idList, self::TABLE_NAME));

                $output->writeln('➡️ Найдено: ' . count($rows));
            })(),
            $output,
            [],
        );
    }

    private function testFetchAllBySql(OutputInterface $output, array $buffer): void
    {
        $minPrice = $buffer[0]['price'] ?? 20;

        $output->writeln("🔍 fetchAllBySql (price >= $minPrice ORDER BY price ASC LIMIT 2)");

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output, $minPrice): void {
                $sql = <<<SQL
SELECT * FROM test_data_types
WHERE price >= :min
ORDER BY price ASC
LIMIT 2
SQL;

                $rows = iterator_to_array($this->finder->fetchAllBySql($sql, ['min' => $minPrice]));
                $output->writeln('➡️ Найдено: ' . count($rows));
            })(),
            $output,
            [],
        );
    }

    private function testFetchOneBySql(OutputInterface $output, array $buffer): void
    {
        $price = $buffer[1]['price'] ?? 30;

        $output->writeln("🔍 fetchOneBySql (price = $price)");

        $this->runBenchmark(
            fn (array $unused) => (function () use ($output, $price): void {
                $sql = 'SELECT * FROM test_data_types WHERE price = :price';

                $row = $this->finder->fetchOneBySql($sql, ['price' => $price]);

                $output->writeln($row
                    ? '✅ Найдена запись: ' . json_encode($row)
                    : '❌ Запись не найдена');
            })(),
            $output,
            [],
        );
    }
}
