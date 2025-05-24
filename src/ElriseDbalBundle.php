<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle;

use Doctrine\DBAL\Types\Type;
use Elrise\Bundle\DbalBundle\DBAL\Type\Float4ArrayType;
use Elrise\Bundle\DbalBundle\DBAL\Type\FloatArrayType;
use Elrise\Bundle\DbalBundle\DependencyInjection\DoctrineDbalExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ElriseDbalBundle extends Bundle
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

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DoctrineDbalExtension();
    }
}
