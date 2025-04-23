<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Service\Serialize;

final readonly class DtoDeserializerFactory
{
    public function __construct(
        private SymfonyDtoDeserializer $symfonyDeserializer,
        private ?AutoMapperDtoDeserializer $autoMapperDeserializer = null,
        private bool $useAutoMapper = false,
    ) {
    }

    public function create(): DtoDeserializerInterface
    {
        $deserializer = $this->useAutoMapper && $this->autoMapperDeserializer !== null
            ? $this->autoMapperDeserializer
            : $this->symfonyDeserializer;

        return new DtoDeserializer($deserializer);
    }
}
