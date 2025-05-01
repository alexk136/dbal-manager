<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Service\Serialize;

final readonly class DtoDeserializer implements DtoDeserializerInterface
{
    public function __construct(
        private DtoDeserializerInterface $innerDeserializer,
    ) {
    }

    public function denormalize(array $data, string $type): object
    {
        return $this->innerDeserializer->denormalize($data, $type);
    }
}
