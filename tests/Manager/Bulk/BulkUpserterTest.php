<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Manager\Bulk;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Manager\Bulk\BulkUpserter;
use ITech\Bundle\DbalBundle\Sql\Builder\MysqlSqlBuilder;
use ITech\Bundle\DbalBundle\Sql\Builder\UpsertReplaceType;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;
use PHPUnit\Framework\TestCase;

final class BulkUpserterTest extends TestCase
{
    private BulkUpserter $executor;
    private Connection $connection;

    public function testUpsertBulkReturnsZeroOnEmptyInput(): void
    {
        $result = $this->executor->upsertMany('test_table', [], []);
        $this->assertSame(0, $result);
    }

    public function testUpsertBulkSimpleReplace(): void
    {
        $paramsList = [
            ['id' => 1, 'name' => 'Alex'],
            ['id' => 2, 'name' => 'John'],
        ];
        $replaceFields = ['name'];

        $expectedSql = 'INSERT INTO `users` (`id`, `name`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?), (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = VALUES(updated_at)';

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $this->countOf(8), $this->countOf(8))
            ->willReturn(42);

        $result = $this->executor->upsertMany('users', $paramsList, $replaceFields);

        $this->assertSame(42, $result);
    }

    public function testUpsertBulkWithIncrementReplace(): void
    {
        $paramsList = [
            ['id' => 1, 'count' => 5],
            ['id' => 2, 'count' => 10],
        ];
        $replaceFields = [
            ['count', UpsertReplaceType::Increment],
        ];

        $expectedSql = 'INSERT INTO `counter` (`id`, `count`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?), (?, ?, ?, ?) ON DUPLICATE KEY UPDATE count = count + VALUES(count), updated_at = VALUES(updated_at)';

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $this->countOf(8), $this->countOf(8))
            ->willReturn(42);

        $result = $this->executor->upsertMany('counter', $paramsList, $replaceFields);
        $this->assertSame(42, $result);
    }

    public function testUpsertBulkWithConditionReplace(): void
    {
        $paramsList = [
            ['id' => 1, 'status' => 'active'],
        ];
        $replaceFields = [
            ['status', UpsertReplaceType::Condition, 'IF(status != "archived", VALUES(status), status)'],
        ];

        $expectedSql = 'INSERT INTO `user_status` (`id`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = IF(status != "archived", VALUES(status), status), updated_at = VALUES(updated_at)';

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($expectedSql, $this->countOf(4), $this->countOf(4))
            ->willReturn(42);

        $result = $this->executor->upsertMany('user_status', $paramsList, $replaceFields);
        $this->assertSame(42, $result);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $sqlBuilder = new MysqlSqlBuilder(new QuestionMarkPlaceholderStrategy());

        $config = new DbalBundleConfig(
            fieldNames: [
                BundleConfigurationInterface::ID_NAME => 'id',
                BundleConfigurationInterface::CREATED_AT_NAME => 'created_at',
                BundleConfigurationInterface::UPDATED_AT_NAME => 'updated_at',
            ],
        );
        $this->executor = new BulkUpserter($this->connection, $sqlBuilder, $config);
    }
}
