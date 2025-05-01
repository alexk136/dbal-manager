<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Sql\Placeholder;

use BackedEnum;
use Doctrine\DBAL\ParameterType;
use Elrise\Bundle\DbalBundle\DBAL\DbalParameterType;
use Elrise\Bundle\DbalBundle\Utils\ArraySerializer;
use Elrise\Bundle\DbalBundle\Utils\DbalTypeGuesser;
use InvalidArgumentException;

final class QuestionMarkPlaceholderStrategy implements PlaceholderStrategyInterface
{
    public function formatValue(mixed $value): string
    {
        return '?';
    }

    public function prepareBulkParameterLists(array $batchRows, ?array $whereFields = null, ?string $platform = null): array
    {
        if (empty($batchRows)) {
            throw new InvalidArgumentException('Batch rows must not be empty');
        }

        $mergedParams = [];
        $mergedTypes = [];

        if ($whereFields !== null) {
            $whereFieldFlip = array_flip($whereFields);

            // Вычисляем все поля для SET (те, которые не входят в whereFields)
            $firstRow = reset($batchRows);
            $setColumns = array_keys(array_diff_key($firstRow, $whereFieldFlip));

            // Сначала собираем параметры для каждого поля SET
            foreach ($setColumns as $column) {
                foreach ($batchRows as $row) {
                    $whereFieldsData = array_intersect_key($row, $whereFieldFlip);

                    // Для CASE нужно id + значение
                    [$whereValues, $whereTypes] = $this->extractValuesAndTypes($whereFieldsData, $platform);
                    [$value, $type] = $this->extractSingleValueAndType($row[$column], $platform);

                    if (!$value && !$type) {
                        continue;
                    }

                    $mergedParams = array_merge($mergedParams, $whereValues, [$value]);
                    $mergedTypes = array_merge($mergedTypes, $whereTypes, [$type]);
                }
            }

            // После всех CASE добавляем id-шники для WHERE
            foreach ($batchRows as $row) {
                [$whereValues, $whereTypes] = $this->extractValuesAndTypes(array_intersect_key($row, $whereFieldFlip), $platform);

                $mergedParams = array_merge($mergedParams, $whereValues);
                $mergedTypes = array_merge($mergedTypes, $whereTypes);
            }
        } else {
            // Если whereFields нет — просто обычная вставка
            foreach ($batchRows as $params) {
                foreach ($params as $value) {
                    [$paramValue, $paramType] = $this->extractSingleValueAndType($value, $platform);

                    if (!$paramValue && !$paramType) {
                        continue;
                    }

                    $mergedParams[] = $paramValue;
                    $mergedTypes[] = $paramType;
                }
            }
        }

        return [$mergedParams, $mergedTypes];
    }

    private function extractSingleValueAndType(mixed $value, ?string $platform): array
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if (is_array($value)) {
            if (empty($value)) {
                return [null, ParameterType::STRING];
            }

            $actualValue = $value[0];
            $actualType = $value[1] ?? null;

            if ($actualValue instanceof BackedEnum) {
                $actualValue = $actualValue->value;
            }

            $type = match (true) {
                is_int($actualType) => DbalTypeGuesser::mapLegacyType($actualType),
                $actualType instanceof ParameterType => DbalTypeGuesser::fromDoctrine($actualType),
                $actualType instanceof DbalParameterType => $actualType,
                $actualType === null => DbalTypeGuesser::guessParameterType($actualValue),
                default => throw new InvalidArgumentException(sprintf('Unexpected type for value: %s', get_debug_type($actualType))),
            };

            $value = $actualValue;
        } else {
            $type = DbalTypeGuesser::guessParameterType($value);
        }

        if (in_array($type, [DbalParameterType::ARRAY, DbalParameterType::FLOAT_ARRAY, DbalParameterType::JSON], true)) {
            $value = ArraySerializer::serialize($value, $platform);
        }

        if ($type === DbalParameterType::DEFAULT) {
            return [null, null];
        }

        return [$value, DbalTypeGuesser::toDoctrine($type)];
    }

    private function extractValuesAndTypes(array $map, ?string $platform): array
    {
        $values = [];
        $types = [];

        foreach ($map as $value) {
            [$v, $t] = $this->extractSingleValueAndType($value, $platform);
            $values[] = $v;
            $types[] = $t;
        }

        return [$values, $types];
    }
}
