<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Utils;

final readonly class DtoDeserializer implements DtoDeserializerInterface
{
    public function __construct(
        private DtoDeserializerInterface $innerDeserializer
    ) {}

    public function denormalize(array $data, string $type): object
    {
        return $this->innerDeserializer->denormalize($data, $type);
    }
}
