<?php

declare(strict_types=1);

namespace ITech\DbalBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_dbal');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            // Переопределение стандартных имён полей (id -> uid)
            ->arrayNode('field_names')
                ->addDefaultsIfNotSet()
                    ->children()
                    ->scalarNode('id')->defaultValue('id')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
