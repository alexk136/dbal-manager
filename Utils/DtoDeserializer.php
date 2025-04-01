<?php

declare(strict_types=1);

namespace ITech\DbalBundle\Utils;

final readonly class DtoDeserializer implements DtoDeserializerInterface
{
    public function __construct(
        private SymfonyDtoDeserializer $symfonyDeserializer,
        private ?AutoMapperDtoDeserializer $autoMapperDeserializer = null,
        private bool $useAutoMapper = false,
    ) {
    }

    public function denormalize(array $data, string $type): object
    {
        if ($this->useAutoMapper && $this->autoMapperDeserializer !== null) {
            return $this->autoMapperDeserializer->denormalize($data, $type);
        }

        return $this->symfonyDeserializer->denormalize($data, $type);
    }
}
