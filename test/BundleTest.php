<?php

namespace Paysera\Bundle\LockBundle\Test;

use Paysera\Bundle\LockBundle\Service\LockManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class BundleTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    protected function setUp()
    {
        require_once __DIR__ . '/TestKernel.php';

        $kernel = new \TestKernel('test', true);
        $kernel->boot();
        $this->container = $kernel->getContainer();
    }

    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove([
            __DIR__ . '/cache',
            __DIR__ . '/logs',
        ]);
    }

    public function testLockManagerConfiguration()
    {
        $ttw = $this->container->getParameter('paysera_lock.ttw');
        $this->assertEquals(10, $ttw);

        $ttl = $this->container->getParameter('paysera_lock.ttl');
        $this->assertNull($ttl);

        $lockManager = $this->container->get('paysera_lock.lock_manager');
        $this->assertInstanceOf(LockManager::class, $lockManager);
    }
}
