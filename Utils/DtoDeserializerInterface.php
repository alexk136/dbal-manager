<?php

declare(strict_types=1);

namespace ITech\DbalBundle\Utils;

interface DtoDeserializerInterface
{
    public function denormalize(array $data, string $type): object;
}
