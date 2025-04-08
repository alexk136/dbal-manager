<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Builder;

interface SqlBuilderInterface
{
    public function getSelectSql(string $tableName, array $fields, string $id): string;
    public function getInsertSql(string $tableName, array $params, bool $isIgnore = false): string;
    public function getUpsertSql(string $tableName, array $params, array $replaceFields): string;
}
