<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Test\Service;

use Paysera\Bundle\LockBundle\Service\LockManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\StoreInterface;

class LockManagerTest extends TestCase
{
    /**
     * @var int[] seconds passed to sleep() by LockManager, recorded by sleep.php
     */
    public static $sleeps = [];

    protected function setUp(): void
    {
        self::$sleeps = [];
    }

    public function testCreateLockReturnsALockOnTheResourceThatIsNotAcquiredAndHasNoTtl(): void
    {
        // symfony/lock 4.4.0 types the factory's store as StoreInterface, which 5.0 removed
        $storeInterface = interface_exists(StoreInterface::class) ? StoreInterface::class : PersistingStoreInterface::class;
        $store = $this->createMock($storeInterface);
        $store->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Key $key): bool {
                return (string) $key === 'invoice-42';
            }))
        ;
        $store->expects($this->never())
            ->method('putOffExpiration')
        ;
        // the lock's destructor asks; symfony/lock below 6.0 declares no return type here, so the double must answer
        $store->method('exists')
            ->willReturn(false)
        ;

        $lock = (new LockManager(new LockFactory($store), 5))->createLock('invoice-42');

        // the store double's expectations are the assertions: saved once, with this key, and never given an expiry
        $lock->acquire();
    }

    public function testAcquireReturnsTrueWhenTheFirstAttemptSucceeds(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())
            ->method('acquire')
            ->willReturn(true)
        ;

        $this->assertTrue((new LockManager($this->createMock(LockFactory::class), 5))->acquire($lock));
        $this->assertSame([], self::$sleeps);
    }

    public function testAcquireTriesAgainEverySecondUntilTheLockIsFree(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->exactly(3))
            ->method('acquire')
            ->willReturnOnConsecutiveCalls(false, false, true)
        ;

        $this->assertTrue((new LockManager($this->createMock(LockFactory::class), 5))->acquire($lock));
        $this->assertSame([1, 1], self::$sleeps);
    }

    /**
     * @dataProvider ttlDataProvider
     *
     * @param int[] $expectedSleeps
     */
    public function testAcquireGivesUpAfterTtlAttemptsWithoutWaitingAfterTheLast(
        int $ttl,
        int $expectedAttempts,
        array $expectedSleeps
    ): void {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->exactly($expectedAttempts))
            ->method('acquire')
            ->willReturn(false)
        ;

        $this->expectException(LockAcquiringException::class);
        $this->expectExceptionMessage('Failed to acquire lock, wait time expired');
        try {
            (new LockManager($this->createMock(LockFactory::class), $ttl))->acquire($lock);
        } finally {
            $this->assertSame($expectedSleeps, self::$sleeps);
        }
    }

    /**
     * @return array<string, array{int, int, int[]}>
     */
    public static function ttlDataProvider(): array
    {
        return [
            'ttl 3: three attempts, two waits' => [3, 3, [1, 1]],
            'ttl 1: one attempt, no wait' => [1, 1, []],
            'ttl 0: one attempt, no wait' => [0, 1, []],
            'negative ttl: one attempt, no wait' => [-1, 1, []],
        ];
    }

    public function testAcquireDoesNotRetryWhenTheStoreFails(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())
            ->method('acquire')
            ->willThrowException(new RuntimeException('store is down'))
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('store is down');
        (new LockManager($this->createMock(LockFactory::class), 5))->acquire($lock);
    }

    public function testCreateAcquiredHoldsTheLockUntilItIsReleased(): void
    {
        $factory = new LockFactory(new FlockStore());
        $lockManager = new LockManager($factory, 5);
        $noWait = new LockManager($factory, 1);
        // FlockStore locks files in the shared temp directory: a name per run keeps parallel runs apart
        $resource = uniqid('lock-manager-test-', true);

        $lock = $lockManager->createAcquired($resource);
        $this->assertTrue($lock->isAcquired());

        try {
            $noWait->createAcquired($resource);
            $this->fail('a second lock on the same resource was acquired');
        } catch (LockAcquiringException $exception) {
            $this->assertSame('Failed to acquire lock, wait time expired', $exception->getMessage());
        }

        $lockManager->release($lock);
        $this->assertFalse($lock->isAcquired());

        $again = $noWait->createAcquired($resource);
        $this->assertTrue($again->isAcquired());
        $again->release();
    }

    public function testReleaseReleasesTheGivenLock(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())
            ->method('release')
        ;

        (new LockManager($this->createMock(LockFactory::class), 5))->release($lock);
    }
}
