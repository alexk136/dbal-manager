<?php

declare(strict_types=1);

namespace ITech\DbalBundle\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Configuration as DoctrineConfiguration;
use ITech\DbalBundle\DBAL\DbalConnection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DbalExtension extends Extension implements PrependExtensionInterface
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $doctrineConfigs = $container->getExtensionConfig('doctrine');
        $configuration = new DoctrineConfiguration((bool) $container->getParameter('kernel.debug'));

        $doctrineConfig = $this->processConfiguration($configuration, $doctrineConfigs);
        $dbalConfig = $doctrineConfig['dbal'] ?? [];

        $wrapperConfig = [];

        if (!empty($dbalConfig['connections'])) {
            foreach (array_keys($dbalConfig['connections']) as $connectionName) {
                $wrapperConfig['dbal']['connections'][$connectionName]['wrapper_class'] = DbalConnection::class;
            }
        } else {
            $wrapperConfig['dbal']['wrapper_class'] = DbalConnection::class;
        }

        $container->prependExtensionConfig('doctrine', $wrapperConfig);
    }
}