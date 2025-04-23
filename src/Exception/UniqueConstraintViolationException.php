<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Exception;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException as DoctrineUniqueConstraintViolationException;
use RuntimeException;

class UniqueConstraintViolationException extends RuntimeException
{
    private string $constraintName;
    private array $conflictingValues;
    private DoctrineUniqueConstraintViolationException $original;

    public function __construct(string $constraintName, array $conflictingValues, DoctrineUniqueConstraintViolationException $previous)
    {
        $this->constraintName = $constraintName;
        $this->conflictingValues = $conflictingValues;
        $this->original = $previous;

        $message = sprintf(
            'Unique constraint "%s" violated for values: %s',
            $constraintName,
            implode(', ', $conflictingValues),
        );

        parent::__construct($message, (int) $previous->getCode(), $previous);
    }

    public function getConstraintName(): string
    {
        return $this->constraintName;
    }

    public function getConflictingValues(): array
    {
        return $this->conflictingValues;
    }

    public function getOriginalException(): DoctrineUniqueConstraintViolationException
    {
        return $this->original;
    }
}
