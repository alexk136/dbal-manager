<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Service\Serialize;

use AutoMapper\AutoMapperInterface;

final readonly class AutoMapperDtoDeserializer implements DtoDeserializerInterface
{
    public function __construct(
        private AutoMapperInterface $autoMapper,
    ) {
    }

    public function denormalize(array $data, string $type): object
    {
        return $this->autoMapper->map($data, $type);
    }
}
