<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager\Iterator;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Generator;
use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\OffsetIteratorInterface;

final class OffsetIterator extends AbstractConfigurableIterator implements OffsetIteratorInterface
{
    /**
     * @throws Exception
     */
    public function iterate(
        string $sql,
        array $params = [],
        array $types = [],
        string $indexField = BundleConfigurationInterface::ID_NAME,
        ?string $dtoClass = null,
    ): Generator {
        $offset = 0;

        while (true) {
            $params['limit'] = $this->chunkSize;
            $params['offset'] = $offset;

            $types['limit'] = ParameterType::INTEGER;
            $types['offset'] = ParameterType::INTEGER;

            $pagedSql = $sql . ' ORDER BY ' . $indexField . ' LIMIT :limit OFFSET :offset';
            $stmt = $this->connection->executeQuery($pagedSql, $params, $types);

            $rows = $stmt->fetchAllAssociative();

            $stmt->free();

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                if ($dtoClass !== null) {
                    yield $this->deserializer->denormalize($row, $dtoClass);
                } else {
                    yield $row;
                }
            }

            $offset += $this->chunkSize;
        }
    }
}
