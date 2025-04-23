<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Service\Serialize;

interface DtoDeserializerInterface
{
    public function denormalize(array $data, string $type): object;
}
