<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Config;

final readonly class DbalBundleConfig implements BundleConfigurationInterface
{
    public function __construct(
        public array $fieldNames = [
            BundleConfigurationInterface::ID_NAME => 'id',
        ],
        public bool $useAutoMapper = false,
        public ?string $defaultDtoGroup = null,
        public int $chunkSize = 1000,
        public string $orderDirection = 'ASC',
        public string $placeholderStrategy = 'question_mark',
        public string $defaultDateTimeFormat = 'Y-m-d H:i:s',
    ) {
    }
}
