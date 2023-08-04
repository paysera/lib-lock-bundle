<?php

namespace Paysera\Bundle\LockBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class PayseraLockExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('paysera_lock.ttw', $config['ttw']);
        $container->setParameter('paysera_lock.ttl', $config['ttl']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->configureLockStore($container, $config);
    }

    private function configureLockStore(ContainerBuilder $container, array $config)
    {
        $storeDefinition = $container->getDefinition('paysera_lock.lock_store');
        $storeDefinition->replaceArgument(0, new Reference($config['redis_client']));
    }
}
