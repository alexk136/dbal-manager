<?php

declare(strict_types=1);

namespace Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\TransactionIsolationLevel;
use ITech\DbalBundle\Service\TransactionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TransactionServiceTest extends TestCase
{
    private MockObject&Connection $connection;
    private TransactionService $service;

    public function testBeginStartsTransactionOnLevelZero(): void
    {
        $this->connection
            ->method('getTransactionNestingLevel')
            ->willReturn(0);

        $this->connection
            ->method('getTransactionIsolation')
            ->willReturn(TransactionIsolationLevel::REPEATABLE_READ);

        $this->connection
            ->expects($this->once())
            ->method('setTransactionIsolation')
            ->with(TransactionIsolationLevel::SERIALIZABLE);

        $this->connection
            ->expects($this->once())
            ->method('beginTransaction');

        $this->service->begin(TransactionIsolationLevel::SERIALIZABLE);
    }

    public function testBeginCreatesSavepointOnNestedLevel(): void
    {
        $this->connection
            ->method('getTransactionNestingLevel')
            ->willReturn(1);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('SAVEPOINT LEVEL1');

        $this->service->begin();
    }

    public function testCommitOnLevelOne(): void
    {
        $this->connection
            ->method('getTransactionNestingLevel')
            ->willReturn(1);

        $this->connection
            ->expects($this->once())
            ->method('commit');

        $this->connection
            ->method('getTransactionIsolation')
            ->willReturn(TransactionIsolationLevel::SERIALIZABLE);

        $this->connection
            ->expects($this->once())
            ->method('setTransactionIsolation')
            ->with(TransactionIsolationLevel::REPEATABLE_READ);

        $this->service->commit();
    }

    public function testCommitOnNestedLevel(): void
    {
        $this->connection
            ->method('getTransactionNestingLevel')
            ->willReturn(2);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('RELEASE SAVEPOINT LEVEL1');

        $this->service->commit();
    }

    public function testRollbackOnLevelOne(): void
    {
        $this->connection
            ->method('getTransactionNestingLevel')
            ->willReturn(1);

        $this->connection
            ->expects($this->once())
            ->method('rollBack');

        $this->connection
            ->method('getTransactionIsolation')
            ->willReturn(TransactionIsolationLevel::SERIALIZABLE);

        $this->connection
            ->expects($this->once())
            ->method('setTransactionIsolation')
            ->with(TransactionIsolationLevel::REPEATABLE_READ);

        $this->service->rollback();
    }

    public function testRollbackOnNestedLevel(): void
    {
        $this->connection
            ->method('getTransactionNestingLevel')
            ->willReturn(3);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('ROLLBACK TO SAVEPOINT LEVEL2');

        $this->service->rollback();
    }

    public function testTransactionalSuccess(): void
    {
        $this->connection
            ->method('getTransactionNestingLevel')
            ->willReturnOnConsecutiveCalls(0, 1);

        $this->connection
            ->method('getTransactionIsolation')
            ->willReturn(TransactionIsolationLevel::REPEATABLE_READ);

        $this->connection
            ->expects($this->once())
            ->method('beginTransaction');

        $this->connection
            ->expects($this->once())
            ->method('commit');

        $result = $this->service->transactional(static fn () => 'ok');

        $this->assertSame('ok', $result);
    }

    public function testTransactionalWithException(): void
    {
        $this->connection
            ->method('getTransactionNestingLevel')
            ->willReturn(0);

        $this->connection
            ->expects($this->once())
            ->method('beginTransaction');

        $this->expectException(RuntimeException::class);

        $this->service->transactional(static function (): void {
            throw new RuntimeException('failure');
        });
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->service = new TransactionService($this->connection);
    }
}
