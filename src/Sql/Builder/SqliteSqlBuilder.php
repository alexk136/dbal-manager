<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Builder;

class SqliteSqlBuilder implements SqlBuilderInterface
{
    public function getSelectSql(string $tableName, array $fields, string $id): string
    {
        // TODO: Implement getSelectSql() method.
    }

    public function getInsertSql(string $tableName, array $params, bool $isIgnore = false): string
    {
        // TODO: Implement getInsertSql() method.
    }

    public function getUpsertSql(string $tableName, array $params, array $replaceFields): string
    {
        // TODO: Implement getUpsertSql() method.
    }
}
