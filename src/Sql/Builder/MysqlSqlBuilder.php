<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Builder;

use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;

final class MysqlSqlBuilder extends AbstractSqlBuilder implements SqlBuilderInterface
{
    private string $platform = MysqlSqlBuilder::class;

    public function getInsertBulkSql(string $tableName, array $paramsList, bool $isIgnore = false): string
    {
        $this->validateTableName($tableName);

        if (empty($paramsList)) {
            throw new InvalidArgumentException('paramsList must not be empty');
        }

        $fields = array_keys($paramsList[0]);
        $this->validateFieldNames($fields);

        $rowsCount = count($paramsList);
        $cacheKey = sprintf('%s|%s|%d', $tableName, $isIgnore ? 'IGNORE' : 'INSERT', $rowsCount);

        if (!isset($this->sqlCache[$cacheKey])) {
            $fieldList = implode(', ', array_map(static fn ($f) => "`$f`", $fields));
            $sqlPrefix = sprintf('%s INTO `%s` (%s) VALUES',
                $isIgnore ? 'INSERT IGNORE' : 'INSERT',
                $tableName,
                $fieldList,
            );

            $valueRows = array_map(
                fn (array $row) => sprintf('(%s)', implode(', ', $this->getValues($row))),
                $paramsList,
            );

            $this->sqlCache[$cacheKey] = $sqlPrefix . ' ' . implode(', ', $valueRows);
            $this->limitCacheSize();
        }

        return $this->sqlCache[$cacheKey];
    }

    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null): array
    {
        if (!empty($whereFields)) {
            $this->validateFieldNames($whereFields);
        }

        return $this->placeholder->prepareBulkParameterLists($batchRows, $whereFields, $this->platform);
    }

    public function getUpdateBulkSql(string $tableName, array $paramsList, array $whereFields): string
    {
        $this->validateTableName($tableName);

        if (empty($whereFields)) {
            throw new InvalidArgumentException('Bulk update requires at least one where-field to generate CASE conditions.');
        }

        if (empty($paramsList)) {
            throw new InvalidArgumentException('paramsList must not be empty');
        }

        $fields = array_keys($paramsList[0]);
        $this->validateFieldNames($fields);
        $this->validateFieldNames($whereFields);

        $rowsCount = count($paramsList);
        $fieldList = implode(',', array_keys($paramsList[0]));
        $whereKey = implode(',', $whereFields);
        $cacheKey = sprintf('%s|UPDATE|%s|%d|%s', $tableName, $whereKey, $rowsCount, $fieldList);

        if (!isset($this->sqlCache[$cacheKey])) {
            $whereFieldMap = array_flip($whereFields);
            $whenExpressions = [];
            $whereConditions = [];

            foreach ($paramsList as $params) {
                $whereParts = [];

                foreach ($whereFields as $field) {
                    $value = $this->placeholder->formatValue($params[$field]);
                    $whereParts[] = sprintf('`%s` = %s', $field, $value);
                }

                $whereExpr = '(' . implode(' AND ', $whereParts) . ')';
                $whereKeyValue = implode('-', array_map(static fn ($field) => $params[$field], $whereFields));
                $whereConditions[$whereKeyValue] = $whereExpr;

                foreach ($params as $field => $value) {
                    if (isset($whereFieldMap[$field])) {
                        continue;
                    }

                    $formattedValue = $this->placeholder->formatValue($value);
                    $whenExpressions[$field][] = sprintf('WHEN %s THEN %s', $whereExpr, $formattedValue);
                }
            }

            $setClauses = array_map(
                static fn ($field, $cases) => sprintf('`%s` = CASE %s ELSE `%s` END', $field, implode(' ', $cases), $field),
                array_keys($whenExpressions),
                $whenExpressions,
            );

            $whereClause = implode(' OR ', array_values($whereConditions));

            $this->sqlCache[$cacheKey] = sprintf(
                'UPDATE `%s` SET %s WHERE %s',
                $tableName,
                implode(', ', $setClauses),
                $whereClause,
            );
            $this->limitCacheSize();
        }

        return $this->sqlCache[$cacheKey];
    }

    public function getUpsertBulkSql(string $tableName, array $paramsList, array $replaceFields, array $fieldNames = []): string
    {
        $this->validateTableName($tableName);

        if (empty($paramsList)) {
            throw new InvalidArgumentException('paramsList must not be empty');
        }

        $fields = array_keys($paramsList[0]);
        $this->validateFieldNames($fields);
        $this->validateFieldNames(array_map(
            static fn ($field) => is_array($field) ? $field[0] : $field,
            $replaceFields,
        ));

        $insertSql = $this->getInsertBulkSql($tableName, $paramsList);
        $replaceFields = $this->updateReplaceFields($replaceFields, $fieldNames);

        $replacements = array_map(static function ($field) {
            if (!\is_array($field)) {
                return sprintf('`%1$s` = VALUES(`%1$s`)', $field);
            }

            [$column, $type, $condition] = $field + [null, null, null];

            return match ($type) {
                UpsertReplaceType::Increment => sprintf('`%1$s` = `%1$s` + VALUES(`%1$s`)', $column),
                UpsertReplaceType::Decrement => sprintf('`%1$s` = `%1$s` - VALUES(`%1$s`)', $column),
                UpsertReplaceType::Condition => sprintf('`%1$s` = %2$s', $column, $condition),
                default => throw new InvalidArgumentException("Unknown UPSERT type: $type"),
            };
        }, $replaceFields);

        $cacheKey = sprintf(
            '%s|UPSERT|%d|%s|%s',
            $tableName,
            count($paramsList),
            implode(',', array_map(
                static fn ($field) => is_array($field) ? implode(':', [$field[0], $field[1] instanceof UpsertReplaceType ? $field[1]->value : $field[1], $field[2] ?? '']) : $field,
                $replaceFields,
            )),
            implode(',', array_values($fieldNames)),
        );

        if (!isset($this->sqlCache[$cacheKey])) {
            $this->sqlCache[$cacheKey] = sprintf('%s ON DUPLICATE KEY UPDATE %s', $insertSql, implode(', ', $replacements));
            $this->limitCacheSize();
        }

        return $this->sqlCache[$cacheKey];
    }

    public function getDeleteBulkSql(string $tableName, array $idList): string
    {
        $this->validateTableName($tableName);

        if (empty($idList)) {
            throw new InvalidArgumentException('idList must not be empty');
        }

        $rowsCount = count($idList);
        $cacheKey = sprintf('%s|DELETE|BY_ID|%d', $tableName, $rowsCount);

        if (!isset($this->sqlCache[$cacheKey])) {
            $placeholderList = implode(', ', array_fill(0, $rowsCount, '?'));

            $this->sqlCache[$cacheKey] = sprintf(
                'DELETE FROM `%s` WHERE `id` IN (%s)',
                $tableName,
                $placeholderList,
            );
            $this->limitCacheSize();
        }

        return $this->sqlCache[$cacheKey];
    }

    private function updateReplaceFields(array $replaceFields, $fieldNames): array
    {
        $updatedAtField = $fieldNames[BundleConfigurationInterface::UPDATED_AT_NAME] ?? null;

        if ($updatedAtField !== null && !in_array($updatedAtField, $replaceFields)) {
            $replaceFields[] = $updatedAtField;
        }

        return $replaceFields;
    }
}
