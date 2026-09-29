<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\DependencyInjection;

use Paysera\Bundle\LockBundle\Service\LockManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\RedisStore;

class PayseraLockExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('paysera_lock.ttl', (int) $config['ttl']);

        $container->register('paysera_lock.lock_store', RedisStore::class)
            ->setArguments([new Reference($config['redis_client'])])
        ;
        $container->register('paysera_lock.lock_factory', LockFactory::class)
            ->setArguments([new Reference('paysera_lock.lock_store')])
        ;
        $container->register('paysera_lock.lock_manager', LockManager::class)
            ->setArguments([new Reference('paysera_lock.lock_factory'), '%paysera_lock.ttl%'])
        ;
    }
}
