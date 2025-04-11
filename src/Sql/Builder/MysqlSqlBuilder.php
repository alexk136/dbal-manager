<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Builder;

use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Sql\Placeholder\PlaceholderStrategyInterface;

readonly class MysqlSqlBuilder implements SqlBuilderInterface
{
    public function __construct(
        private PlaceholderStrategyInterface $placeholder,
    ) {
    }

    public function getInsertBulkSql(string $tableName, array $paramsList, bool $isIgnore = false): string
    {
        if (empty($paramsList)) {
            throw new InvalidArgumentException('paramsList must not be empty');
        }

        $fields = array_keys($paramsList[0]);
        $fieldList = implode(', ', $fields);

        $sqlPrefix = sprintf('%s INTO `%s` (%s) VALUES',
            $isIgnore ? 'INSERT IGNORE' : 'INSERT',
            $tableName,
            $fieldList,
        );

        $valueRows = array_map(
            fn (array $row) => sprintf('(%s)', implode(', ', $this->getValues($row))),
            $paramsList,
        );

        return $sqlPrefix . ' ' . implode(', ', $valueRows);
    }

    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null): array
    {
        return $this->placeholder->prepareBulkParameterLists($batchRows, $whereFields);
    }

    public function getUpdateBulkSql(string $tableName, array $paramsList, array $whereFields): string
    {
        $whereFieldMap = array_flip($whereFields);
        $whenExpressions = [];
        $whereConditions = [];

        foreach ($paramsList as $params) {
            $whereParts = [];

            foreach ($whereFields as $field) {
                $value = $this->placeholder->formatValue($params[$field]);
                $whereParts[] = sprintf('%s = %s', $field, $value);
            }

            $whereExpr = '(' . implode(' AND ', $whereParts) . ')';
            $whereKey = implode('-', array_map(static fn ($field) => $params[$field], $whereFields));
            $whereConditions[$whereKey] = $whereExpr;

            foreach ($params as $field => $value) {
                if (isset($whereFieldMap[$field])) {
                    continue; // skip where-fields
                }

                $formattedValue = $this->placeholder->formatValue($value);
                $whenExpressions[$field][] = sprintf('WHEN %s THEN %s', $whereExpr, $formattedValue);
            }
        }

        $setClauses = array_map(
            static fn ($field, $cases) => sprintf('%s = CASE %s ELSE %s END', $field, implode(' ', $cases), $field),
            array_keys($whenExpressions),
            $whenExpressions,
        );

        $whereClause = implode(' OR ', array_values($whereConditions));

        return sprintf('UPDATE `%s` SET %s WHERE %s', $tableName, implode(', ', $setClauses), $whereClause);
    }

    private function getValues(array $row): array
    {
        return array_map(fn ($value) => $this->placeholder->formatValue($value), $row);
    }
}
