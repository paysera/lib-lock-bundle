<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Test\Service;

use Paysera\Bundle\LockBundle\Service\LockManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\StoreInterface;

require_once __DIR__ . '/sleep.php';

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

    public function testCreateLockReturnsALockThatIsNotAcquiredAndHasNoTtl(): void
    {
        // symfony/lock 4.4.0 types the factory's store as StoreInterface, which 5.0 removed
        $storeInterface = interface_exists(StoreInterface::class) ? StoreInterface::class : PersistingStoreInterface::class;
        $store = $this->createMock($storeInterface);
        $store->expects($this->once())->method('save');
        $store->expects($this->never())->method('putOffExpiration');
        $store->method('exists')->willReturn(false);

        $lock = (new LockManager(new LockFactory($store), 5))->createLock('lock-manager-test-create');

        $this->assertFalse($lock->isAcquired());
        $this->assertTrue($lock->acquire(), 'a lock without a TTL is saved and never has its expiry extended');
    }

    public function testAcquireReturnsTrueWhenTheFirstAttemptSucceeds(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())->method('acquire')->willReturn(true);

        $this->assertTrue((new LockManager($this->createMock(LockFactory::class), 5))->acquire($lock));
        $this->assertSame([], self::$sleeps);
    }

    public function testAcquireTriesAgainEverySecondUntilTheLockIsFree(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->exactly(3))->method('acquire')->willReturnOnConsecutiveCalls(false, false, true);

        $this->assertTrue((new LockManager($this->createMock(LockFactory::class), 5))->acquire($lock));
        $this->assertSame([1, 1], self::$sleeps);
    }

    /**
     * @dataProvider ttlDataProvider
     */
    public function testAcquireGivesUpAfterTtlAttemptsWithoutWaitingAfterTheLast(
        int $ttl,
        int $expectedAttempts,
        array $expectedSleeps
    ): void {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->exactly($expectedAttempts))->method('acquire')->willReturn(false);

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
        $lock->expects($this->once())->method('acquire')->willThrowException(new RuntimeException('store is down'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('store is down');
        (new LockManager($this->createMock(LockFactory::class), 5))->acquire($lock);
    }

    public function testCreateAcquiredHoldsTheLockUntilItIsReleased(): void
    {
        $factory = new LockFactory(new FlockStore());
        $lockManager = new LockManager($factory, 5);
        $noWait = new LockManager($factory, 1);

        $lock = $lockManager->createAcquired('lock-manager-test-acquired');
        $this->assertTrue($lock->isAcquired());

        try {
            $noWait->createAcquired('lock-manager-test-acquired');
            $this->fail('a second lock on the same resource was acquired');
        } catch (LockAcquiringException $exception) {
            $this->assertSame('Failed to acquire lock, wait time expired', $exception->getMessage());
        }

        $lockManager->release($lock);
        $this->assertFalse($lock->isAcquired());

        $again = $noWait->createAcquired('lock-manager-test-acquired');
        $this->assertTrue($again->isAcquired());
        $again->release();
    }

    public function testReleaseReleasesTheGivenLock(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())->method('release');

        (new LockManager($this->createMock(LockFactory::class), 5))->release($lock);
    }
}
