<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Service\Dto;

interface DtoFieldExtractorInterface
{
    public function getFields(string $dtoClass): array;
    public function getFieldValue(object $dto, string $field): mixed;
}
