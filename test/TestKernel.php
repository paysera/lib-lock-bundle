<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Test;

use Paysera\Bundle\LockBundle\PayseraLockBundle;
use Snc\RedisBundle\SncRedisBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class TestKernel extends Kernel implements CompilerPassInterface
{
    public function registerBundles(): array
    {
        $bundles = [];

        if (in_array($this->getEnvironment(), ['test'])) {
            $bundles[] = new FrameworkBundle();
            $bundles[] = new SncRedisBundle();
            $bundles[] = new PayseraLockBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config.yml');
    }

    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('paysera_lock.lock_manager')->setPublic(true);
    }
}
