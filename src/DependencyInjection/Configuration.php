<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\DependencyInjection;

use ITech\Bundle\DbalBundle\Config\BundleConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_dbal');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            ->arrayNode('field_names')
            ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode(BundleConfigurationInterface::ID_NAME)->cannotBeEmpty()->defaultValue('id')->end()
                    ->scalarNode(BundleConfigurationInterface::CREATED_AT_NAME)->cannotBeEmpty()->defaultValue('createdAt')->end()
                    ->scalarNode(BundleConfigurationInterface::UPDATED_AT_NAME)->cannotBeEmpty()->defaultValue('updatedAt')->end()
                    ->scalarNode(BundleConfigurationInterface::DELETED_AT_NAME)->cannotBeEmpty()->defaultValue('deletedAt')->end()
                ->end()
            ->end()

            // Включение AutoMapper
            ->booleanNode('use_auto_mapper')
                ->defaultFalse()
            ->end()

            // Группа десериализации для Symfony Serializer
            ->scalarNode('default_dto_group')
                ->defaultNull()
            ->end()
        ;

        return $treeBuilder;
    }
}
