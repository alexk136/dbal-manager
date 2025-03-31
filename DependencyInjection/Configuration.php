<?php

declare(strict_types=1);

namespace ITech\DbalBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_dbal');

        return $treeBuilder;
    }
}