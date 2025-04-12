<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Manager;

use ITech\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkUpdaterInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\BulkUpserterInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\CursorIteratorInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\DbalFinderInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\DbalMutatorInterface;
use ITech\Bundle\DbalBundle\Manager\Contract\OffsetIteratorInterface;

final readonly class DbalManagerFactory
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

    public function create(): DbalManager
    {
        return new DbalManager(
            $this->finder,
            $this->mutator,
            $this->cursorIterator,
            $this->offsetIterator,
            $this->bulkInserter,
            $this->bulkUpdater,
            $this->bulkUpserter,
        );
    }
}
