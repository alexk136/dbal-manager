<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Sql\Placeholder;

use InvalidArgumentException;

final class QuestionMarkPlaceholderStrategy implements PlaceholderStrategyInterface
{
    public function formatValue(mixed $value): string
    {
        return '?';
    }

    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null): array
    {
        if (empty($batchRows)) {
            throw new InvalidArgumentException('paramsList must not be empty');
        }

        $mergedParams = [];
        $mergedTypes = [];

        if ($whereFields) {
            $whereFieldFlip = array_flip($whereFields);
            $wherePartValueList = [];
            $wherePartTypeList = [];

            $setsList = array_map(
                static fn (array $params) => array_diff_key($params, $whereFieldFlip),
                $batchRows,
            );

            $fieldList = array_keys(array_merge(...$setsList));

            foreach ($fieldList as $field) {
                foreach ($batchRows as $params) {
                    if (!array_key_exists($field, $params)) {
                        continue;
                    }

                    $whereFieldValueMap = array_intersect_key($params, $whereFieldFlip);
                    [$whereValues, $whereTypes] = $this->extractValuesAndTypes($whereFieldValueMap);

                    [$value, $type] = $this->extractSingleValueAndType($params[$field]);

                    $mergedParams = array_merge($mergedParams, $whereValues, [$value]);
                    $mergedTypes = array_merge($mergedTypes, $whereTypes, [$type]);

                    $wherePartKey = implode('-', $whereValues);
                    $wherePartValueList[$wherePartKey] = $whereValues;
                    $wherePartTypeList[$wherePartKey] = $whereTypes;
                }
            }

            $mergedParams = array_merge($mergedParams, ...array_values($wherePartValueList));
            $mergedTypes = array_merge($mergedTypes, ...array_values($wherePartTypeList));
        } else {
            foreach ($batchRows as $params) {
                foreach ($params as $value) {
                    [$paramValue, $paramType] = $this->extractSingleValueAndType($value);
                    $mergedParams[] = $paramValue;
                    $mergedTypes[] = $paramType;
                }
            }
        }

        return [$mergedParams, $mergedTypes];
    }

    private function extractSingleValueAndType(mixed $value): array
    {
        return is_array($value)
            ? [$value[0], $value[1] ?? null]
            : [$value, null];
    }

    private function extractValuesAndTypes(array $map): array
    {
        $values = [];
        $types = [];

        foreach ($map as $value) {
            [$v, $t] = $this->extractSingleValueAndType($value);
            $values[] = $v;
            $types[] = $t;
        }

        return [$values, $types];
    }
}
