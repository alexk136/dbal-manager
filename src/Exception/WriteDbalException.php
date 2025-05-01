<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Exception;

use Doctrine\DBAL\Exception as DoctrineDbalException;
use RuntimeException;

class WriteDbalException extends RuntimeException
{
    private DoctrineDbalException $previousDbalException;

    public function __construct(DoctrineDbalException $previous)
    {
        $this->previousDbalException = $previous;

        parent::__construct(
            sprintf('DBAL write error: %s', $previous->getMessage()),
            $previous->getCode(),
            $previous,
        );
    }

    public function getDbalException(): DoctrineDbalException
    {
        return $this->previousDbalException;
    }
}
