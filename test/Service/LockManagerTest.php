<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Test\Service;

use Paysera\Bundle\LockBundle\Service\LockManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class LockManagerTest extends TestCase
{
    /**
     * @var LockManager
     */
    private $lockManager;

    /**
     * @var MockObject|LockFactory
     */
    private $lockFactory;

    public function setUp()
    {
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->lockManager = new LockManager(
            $this->lockFactory,
            2,
            1
        );
    }

    public function testCreateLock()
    {
        $lock = $this->createMock(LockInterface::class);
        $this->lockFactory
            ->expects($this->once())
            ->method('createLock')
            ->with('resource', 1)
            ->willReturn($lock)
        ;

        $createdLock = $this->lockManager->createLock('resource');

        $this->assertEquals($lock, $createdLock);
    }

    public function testAcquire()
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->expects($this->exactly(2))
            ->method('acquire')
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        $this->lockManager->acquire($lock);
    }

    public function testAcquireTtw()
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->expects($this->exactly(2))
            ->method('acquire')
            ->willReturnOnConsecutiveCalls(false, false)
        ;

        $this->expectException(LockAcquiringException::class);
        $this->expectExceptionMessage('Failed to acquire lock, wait time expired');

        $this->lockManager->acquire($lock);
    }

    public function testCreateAcquired()
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->expects($this->once())
            ->method('acquire')
            ->willReturn(true)
        ;

        $this->lockFactory
            ->expects($this->once())
            ->method('createLock')
            ->with('resource', 1)
            ->willReturn($lock)
        ;

        $created = $this->lockManager->createAcquired('resource');

        $this->assertEquals($lock, $created);
    }

    public function testRelease()
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->expects($this->once())
            ->method('release')
        ;
        $this->lockManager->release($lock);
    }
}
