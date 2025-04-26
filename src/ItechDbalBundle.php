<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle;

use Doctrine\DBAL\Types\Type;
use ITech\Bundle\DbalBundle\DBAL\Type\Float4ArrayType;
use ITech\Bundle\DbalBundle\DBAL\Type\FloatArrayType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ItechDbalBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if (!Type::hasType(FloatArrayType::NAME)) {
            Type::addType(FloatArrayType::NAME, FloatArrayType::class);
        }

        if (!Type::hasType(Float4ArrayType::NAME)) {
            Type::addType(Float4ArrayType::NAME, Float4ArrayType::class);
        }
    }
}
