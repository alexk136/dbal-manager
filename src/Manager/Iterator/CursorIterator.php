<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager\Iterator;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Elrise\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use Elrise\Bundle\DbalBundle\Enum\CursorIteratorInterface;
use Elrise\Bundle\DbalBundle\Utils\DbalTypeGuesser;
use Elrise\Bundle\DbalBundle\Utils\DtoFieldExtractor;
use Generator;
use InvalidArgumentException;
use RuntimeException;

final class CursorIterator extends AbstractConfigurableIterator implements CursorIteratorInterface
{
    /**
     * @throws Exception
     */
    public function iterate(
        string $tableName,
        string $cursorField = BundleConfigurationInterface::ID_NAME,
        array $initialCursorValues = [0],
        ?string $dtoClass = null,
    ): Generator {
        if (empty($cursorField)) {
            throw new InvalidArgumentException('Cursor field must be defined.');
        }

        $fieldList = $dtoClass !== null
            ? implode(', ', DtoFieldExtractor::getFields($dtoClass))
            : '*';

        $cursorValues = $initialCursorValues;

        while (true) {
            $sql = sprintf(
                'SELECT %s FROM %s WHERE %s > :cursor ORDER BY %s %s LIMIT :limit',
                $fieldList,
                $tableName,
                $cursorField,
                $cursorField,
                $this->orderDirection,
            );

            $cursor = $cursorValues[0];

            $params = [
                'cursor' => $cursor,
                'limit' => $this->chunkSize,
            ];

            $types = [
                'cursor' => DbalTypeGuesser::toDoctrine(DbalTypeGuesser::guessParameterType($cursor)),
                'limit' => ParameterType::INTEGER,
            ];

            $stmt = $this->connection->executeQuery($sql, $params, $types);

            $rows = $stmt->fetchAllAssociative();

            $stmt->free();

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                if ($dtoClass !== null) {
                    $dto = $this->deserializer->denormalize($row, $dtoClass);
                    $cursorValues = [DtoFieldExtractor::getFieldValue($dto, $cursorField)];
                    yield $dto;
                } else {
                    $cursorValue = $row[$cursorField] ?? null;

                    if ($cursorValue === null) {
                        throw new RuntimeException("Missing cursor field '{$cursorField}' in raw row data.");
                    }

                    $cursorValues = [$cursorValue];
                    yield $row;
                }
            }
        }
    }
}
