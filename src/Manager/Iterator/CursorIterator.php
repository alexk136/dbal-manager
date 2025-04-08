<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Iterator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Generator;
use InvalidArgumentException;
use ITech\Bundle\DbalBundle\Config\ConfigurationInterface;
use ITech\Bundle\DbalBundle\Config\DbalBundleConfig;
use ITech\Bundle\DbalBundle\Manager\Contract\CursorIteratorInterface;
use ITech\Bundle\DbalBundle\Service\Serialize\DtoDeserializerInterface;
use ITech\Bundle\DbalBundle\Util\DbalTypeGuesser;
use ITech\Bundle\DbalBundle\Util\DtoFieldExtractor;
use RuntimeException;

final readonly class CursorIterator implements CursorIteratorInterface
{
    public function __construct(
        private Connection $connection,
        private DtoDeserializerInterface $deserializer,
        private DbalBundleConfig $config,
    ) {
    }

    /**
     * @throws Exception
     */
    public function iterate(
        string $tableName,
        string $cursorField = ConfigurationInterface::ID_NAME,
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
                $this->config->orderDirection,
            );

            $cursor = $cursorValues[0];

            $params = [
                'cursor' => $cursor,
                'limit' => $this->config->chunkSize,
            ];

            $types = [
                'cursor' => DbalTypeGuesser::guessParameterType($cursor),
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
