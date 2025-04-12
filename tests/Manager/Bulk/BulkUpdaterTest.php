<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Tests\Manager\Bulk;

use Doctrine\DBAL\Connection;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Manager\Bulk\BulkUpdater;
use ITech\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface;
use PHPUnit\Framework\TestCase;

final class BulkUpdaterTest extends TestCase
{
    private BulkUpdater $updater;
    private Connection $connection;
    private SqlBuilderInterface $sqlBuilder;

    public function testUpdateReturnsZeroOnEmptyInput(): void
    {
        $result = $this->updater->update('users', []);
        $this->assertSame(0, $result);
    }

    public function testUpdateUsesDefaultIdWhenWhereFieldsNotProvided(): void
    {
        $paramsList = [['id' => ['123'], 'name' => ['Alice']]];
        $sql = 'UPDATE users ...';
        $flatParams = ['123', 'Alice'];
        $types = [null, null];

        $this->sqlBuilder->method('getUpdateBulkSql')->willReturn($sql);
        $this->sqlBuilder->method('prepareBulkParameterLists')->willReturn([$flatParams, $types]);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with($sql, $flatParams, $types)
            ->willReturn(1);

        $result = $this->updater->update('users', $paramsList);

        $this->assertEquals(1, $result);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->sqlBuilder = $this->createMock(SqlBuilderInterface::class);

        $config = new DbalBundleConfig(
            fieldNames: [
                BundleConfigurationInterface::ID_NAME => 'id',
                BundleConfigurationInterface::CREATED_AT_NAME => 'created_at',
                BundleConfigurationInterface::UPDATED_AT_NAME => 'updated_at',
            ],
        );

        $this->updater = new BulkUpdater($this->connection, $config, $this->sqlBuilder);
    }
}
