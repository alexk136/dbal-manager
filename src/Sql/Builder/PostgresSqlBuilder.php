<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Builder;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\DBAL\DbalParameterType;

final class PostgresSqlBuilder extends AbstractSqlBuilder implements SqlBuilderInterface
{
    private string $platform = PostgreSQLPlatform::class;

    public function getInsertBulkSql(string $tableName, array $paramsList, bool $isIgnore = false): string
    {
        $this->validateTableName($tableName);

        if (empty($paramsList)) {
            throw new InvalidArgumentException('paramsList must not be empty');
        }

        $fields = array_keys($paramsList[0]);

        // Проверяем, есть ли DEFAULT в paramsList
        $hasDefault = false;

        foreach ($paramsList as $row) {
            foreach ($row as $value) {
                if (self::isDefaultType($value)) {
                    $hasDefault = true;
                    break 2;
                }
            }
        }

        $this->validateFieldNames($fields);

        $rowsCount = count($paramsList);
        $cacheKey = sprintf('%s|%s|%d|%s', $tableName, $isIgnore ? 'IGNORE' : 'INSERT', $rowsCount, $hasDefault ? 'DEFAULT' : 'NO_DEFAULT');

        if (!isset($this->sqlCache[$cacheKey])) {
            $fieldList = implode(', ', array_map(static fn ($f) => '"' . $f . '"', $fields));
            $sqlPrefix = sprintf(
                'INSERT INTO "%s" (%s) VALUES',
                $tableName,
                $fieldList,
            );

            $valueRows = array_map(
                function (array $row) {
                    $values = $this->getSqlReadyValues($row);

                    return sprintf('(%s)', implode(', ', $values));
                },
                $paramsList,
            );

            $insertSql = $sqlPrefix . ' ' . implode(', ', $valueRows);

            if ($isIgnore) {
                $insertSql .= ' ON CONFLICT DO NOTHING';
            }

            $this->sqlCache[$cacheKey] = $insertSql;
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

        $this->validateFieldNames(array_keys($paramsList[0]));
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
                    $whereParts[] = sprintf('"%s" = %s', $field, $value);
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
                static fn ($field, $cases) => sprintf('"%s" = CASE %s ELSE "%s" END', $field, implode(' ', $cases), $field),
                array_keys($whenExpressions),
                $whenExpressions,
            );

            $whereClause = implode(' OR ', array_values($whereConditions));

            $this->sqlCache[$cacheKey] = sprintf(
                'UPDATE "%s" SET %s WHERE %s',
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
        $rowsCount = count($paramsList);
        $replaceFieldsKey = implode(',', array_map(
            static fn ($field) => is_array($field) ? implode(':', [$field[0], $field[1]->value ?? $field[1], $field[2] ?? '']) : $field,
            $replaceFields,
        ));
        $fieldNamesKey = implode(',', array_values($fieldNames));
        $cacheKey = sprintf('%s|UPSERT|%d|%s|%s', $tableName, $rowsCount, $replaceFieldsKey, $fieldNamesKey);

        if (!isset($this->sqlCache[$cacheKey])) {
            $insertSql = $this->getInsertBulkSql($tableName, $paramsList);

            $conflictFields = $this->updateConflictFields($replaceFields, $fieldNames);
            $this->validateFieldNames(array_map(
                static fn ($field) => is_array($field) ? $field[0] : $field,
                $conflictFields,
            ));

            if (empty($conflictFields)) {
                throw new InvalidArgumentException('Conflict fields must be specified for PostgreSQL upsert.');
            }

            $conflictFieldsSql = implode(', ', array_map(
                static fn ($field) => '"' . (is_array($field) ? $field[0] : $field) . '"',
                $conflictFields,
            ));

            $updates = array_map(static function ($field) {
                if (!is_array($field)) {
                    return sprintf('"%1$s" = EXCLUDED."%1$s"', $field);
                }

                [$column, $type, $condition] = $field + [null, null, null];

                return match ($type) {
                    UpsertReplaceType::Increment => sprintf('"%1$s" = "%1$s" + EXCLUDED."%1$s"', $column),
                    UpsertReplaceType::Decrement => sprintf('"%1$s" = "%1$s" - EXCLUDED."%1$s"', $column),
                    UpsertReplaceType::Condition => sprintf('"%1$s" = %2$s', $column, $condition),
                    default => throw new InvalidArgumentException("Unknown UPSERT type: $type"),
                };
            }, $replaceFields);

            $this->sqlCache[$cacheKey] = sprintf(
                '%s ON CONFLICT (%s) DO UPDATE SET %s',
                $insertSql,
                $conflictFieldsSql,
                implode(', ', $updates),
            );
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
                'DELETE FROM "%s" WHERE "id" IN (%s)',
                $tableName,
                $placeholderList,
            );
            $this->limitCacheSize();
        }

        return $this->sqlCache[$cacheKey];
    }

    public static function isDefaultType(mixed $value): bool
    {
        return is_array($value) && count($value) === 2 && $value[1] === DbalParameterType::DEFAULT;
    }

    /**
     * Проверяет имя таблицы, чтобы убедиться, что оно является допустимым идентификатором PostgreSQL.
     */
    private function updateConflictFields(array $replaceFields, array $fieldNames): array
    {
        $identificationField = $fieldNames[BundleConfigurationInterface::ID_NAME] ?? null;

        if ($identificationField !== null && !in_array($identificationField, array_map(
            static fn ($field) => is_array($field) ? $field[0] : $field,
            $replaceFields,
        ))) {
            $replaceFields[] = $identificationField;
        }

        return $replaceFields;
    }

    private function getSqlReadyValues(array $row): array
    {
        return array_map(function ($value) {
            if (self::isDefaultType($value)) {
                return 'DEFAULT';
            }

            return $this->getValues([$value])[0];
        }, $row);
    }
}
