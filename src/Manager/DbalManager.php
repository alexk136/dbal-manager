<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Manager;

use Elrise\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\BulkUpdaterInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\BulkUpserterInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\CursorIteratorInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\DbalFinderInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\DbalMutatorInterface;
use Elrise\Bundle\DbalBundle\Manager\Contract\OffsetIteratorInterface;

final readonly class DbalManager
{
    public function __construct(
        private DbalFinderInterface $finder,
        private DbalMutatorInterface $mutator,
        private CursorIteratorInterface $cursorIterator,
        private OffsetIteratorInterface $offsetIterator,
        private BulkInserterInterface $bulkInserter,
        private BulkUpdaterInterface $bulkUpdater,
        private BulkUpserterInterface $bulkUpserter,
    ) {
    }

    public function finder(): DbalFinderInterface
    {
        return $this->finder;
    }

    public function mutator(): DbalMutatorInterface
    {
        return $this->mutator;
    }

    public function cursorIterator(): CursorIteratorInterface
    {
        return $this->cursorIterator;
    }

    public function offsetIterator(): OffsetIteratorInterface
    {
        return $this->offsetIterator;
    }

    public function bulkInserter(): BulkInserterInterface
    {
        return $this->bulkInserter;
    }

    public function bulkUpdater(): BulkUpdaterInterface
    {
        return $this->bulkUpdater;
    }

    public function bulkUpserter(): BulkUpserterInterface
    {
        return $this->bulkUpserter;
    }
}
