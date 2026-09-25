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

    /**
     * @dataProvider ttlDataProvider
     *
     * @param array<string, mixed> $config
     */
    public function testTtlParameter(array $config, int $expectedTtl): void
    {
        $this->assertSame($expectedTtl, $this->load($config)->getParameter('paysera_lock.ttl'));
    }

    /**
     * @return array<string, array{array<string, mixed>, int}>
     */
    public static function ttlDataProvider(): array
    {
        return [
            'defaults to five seconds' => [['redis_client' => 'app.redis'], 5],
            'read as an integer' => [['ttl' => '10', 'redis_client' => 'app.redis'], 10],
        ];
    }

    /**
     * @dataProvider serviceDefinitionDataProvider
     *
     * @param array{class: string, arguments: array<int, mixed>} $expectedDefinition
     */
    public function testServiceDefinition(string $serviceId, array $expectedDefinition): void
    {
        $definition = $this->load(['redis_client' => 'app.redis'])->getDefinition($serviceId);

        $this->assertSame($expectedDefinition, [
            'class' => $definition->getClass(),
            'arguments' => array_map(function ($argument) {
                return $argument instanceof Reference ? ['service' => (string) $argument] : $argument;
            }, $definition->getArguments()),
        ]);
    }

    /**
     * @return array<string, array{string, array{class: string, arguments: array<int, mixed>}}>
     */
    public static function serviceDefinitionDataProvider(): array
    {
        return [
            'the store is a RedisStore on the configured client' => [
                'paysera_lock.lock_store',
                ['class' => RedisStore::class, 'arguments' => [['service' => 'app.redis']]],
            ],
            'the factory uses the store' => [
                'paysera_lock.lock_factory',
                ['class' => LockFactory::class, 'arguments' => [['service' => 'paysera_lock.lock_store']]],
            ],
            'the lock manager uses the factory and the ttl' => [
                'paysera_lock.lock_manager',
                [
                    'class' => LockManager::class,
                    'arguments' => [['service' => 'paysera_lock.lock_factory'], '%paysera_lock.ttl%'],
                ],
            ],
        ];
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
