<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Test\DependencyInjection;

use Paysera\Bundle\LockBundle\DependencyInjection\PayseraLockExtension;
use Paysera\Bundle\LockBundle\PayseraLockBundle;
use Paysera\Bundle\LockBundle\Service\LockManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\RedisStore;

class PayseraLockExtensionTest extends TestCase
{
    public function testTheBundleRegistersTheExtensionUnderPayseraLock(): void
    {
        $extension = (new PayseraLockBundle())->getContainerExtension();

        $this->assertInstanceOf(PayseraLockExtension::class, $extension);
        $this->assertSame('paysera_lock', $extension->getAlias());
    }

    public function testTtlDefaultsToFiveSeconds(): void
    {
        $container = $this->load(['redis_client' => 'app.redis']);

        $this->assertSame(5, $container->getParameter('paysera_lock.ttl'));
    }

    public function testTtlIsReadAsAnInteger(): void
    {
        $container = $this->load(['ttl' => '10', 'redis_client' => 'app.redis']);

        $this->assertSame(10, $container->getParameter('paysera_lock.ttl'));
    }

    public function testTheStoreIsARedisStoreOnTheConfiguredClient(): void
    {
        $store = $this->load(['redis_client' => 'app.redis'])->getDefinition('paysera_lock.lock_store');

        $this->assertSame(RedisStore::class, $store->getClass());
        $this->assertEquals([new Reference('app.redis')], $store->getArguments());
    }

    public function testTheFactoryUsesTheStore(): void
    {
        $factory = $this->load(['redis_client' => 'app.redis'])->getDefinition('paysera_lock.lock_factory');

        $this->assertSame(LockFactory::class, $factory->getClass());
        $this->assertEquals([new Reference('paysera_lock.lock_store')], $factory->getArguments());
    }

    public function testTheLockManagerUsesTheFactoryAndTheTtl(): void
    {
        $lockManager = $this->load(['redis_client' => 'app.redis'])->getDefinition('paysera_lock.lock_manager');

        $this->assertSame(LockManager::class, $lockManager->getClass());
        $this->assertEquals(
            [new Reference('paysera_lock.lock_factory'), '%paysera_lock.ttl%'],
            $lockManager->getArguments()
        );
    }

    /**
     * @dataProvider invalidConfigurationDataProvider
     *
     * @param array<string, mixed> $config
     */
    public function testTheRedisClientIsRequired(array $config): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load($config);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'missing' => [['ttl' => 5]],
            'empty' => [['redis_client' => '']],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new PayseraLockExtension())->load([$config], $container);

        return $container;
    }
}
