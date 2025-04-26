<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL120Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Sql\Placeholder\QuestionMarkPlaceholderStrategy;

final readonly class SqlBuilderFactory
{
    public function __construct(
        private Connection $connection,
        private string $placeholderStrategy = 'question_mark',
    ) {
    }

    /**
     * @throws Exception
     */
    public function create(): SqlBuilderInterface
    {
        $placeholder = match ($this->placeholderStrategy) {
            'question_mark' => new QuestionMarkPlaceholderStrategy(),
            default => throw new InvalidArgumentException('Unknown placeholder strategy'),
        };

        $platform = $this->connection->getDatabasePlatform();

        $className = get_class($platform);

        return match ($platform::class) {
            MySQLPlatform::class => new MysqlSqlBuilder($placeholder),
            PostgreSQLPlatform::class, PostgreSQL120Platform::class => new PostgresSqlBuilder($placeholder),
            default => throw new InvalidArgumentException("Unsupported platform: {$className}"),
        };
    }
}
