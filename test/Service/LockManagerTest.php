<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Test\Service;

use Exception;
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
     * @var int[]
     */
    public static $sleeps = [];

    protected function setUp(): void
    {
        self::$sleeps = [];
    }

    public function testCreateLockReturnsALockOnTheResourceThatIsNotAcquiredAndHasNoTtl(): void
    {
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
        $store->method('exists')
            ->willReturn(false)
        ;

        $lock = (new LockManager(new LockFactory($store), 5))->createLock('invoice-42');

        $lock->acquire();
    }

    /**
     * @dataProvider successfulAcquireDataProvider
     *
     * @param bool[] $attemptResults
     * @param int[] $expectedSleeps
     */
    public function testAcquireReturnsTrueWhenAnAttemptSucceeds(array $attemptResults, array $expectedSleeps): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->exactly(count($attemptResults)))
            ->method('acquire')
            ->willReturnOnConsecutiveCalls(...$attemptResults)
        ;

        $this->assertTrue((new LockManager($this->createMock(LockFactory::class), 5))->acquire($lock));
        $this->assertSame($expectedSleeps, self::$sleeps);
    }

    /**
     * @return array<string, array{bool[], int[]}>
     */
    public static function successfulAcquireDataProvider(): array
    {
        return [
            'first attempt: no wait' => [[true], []],
            'third attempt: a wait before each retry' => [[false, false, true], [1, 1]],
        ];
    }

    /**
     * @dataProvider failedAcquireDataProvider
     *
     * @param int[] $expectedSleeps
     */
    public function testAcquireThrowsWhenNoAttemptSucceeds(
        int $ttl,
        callable $attempt,
        int $expectedAttempts,
        Exception $expectedException,
        array $expectedSleeps
    ): void {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->exactly($expectedAttempts))
            ->method('acquire')
            ->willReturnCallback($attempt)
        ;

        $this->expectExceptionObject($expectedException);
        try {
            (new LockManager($this->createMock(LockFactory::class), $ttl))->acquire($lock);
        } finally {
            $this->assertSame($expectedSleeps, self::$sleeps);
        }
    }

    /**
     * @return array<string, array{int, callable, int, Exception, int[]}>
     */
    public static function failedAcquireDataProvider(): array
    {
        $held = static function (): bool {
            return false;
        };
        $expired = new LockAcquiringException('Failed to acquire lock, wait time expired');

        return [
            'ttl 3: three attempts, two waits' => [3, $held, 3, $expired, [1, 1]],
            'ttl 1: one attempt, no wait' => [1, $held, 1, $expired, []],
            'ttl 0: one attempt, no wait' => [0, $held, 1, $expired, []],
            'negative ttl: one attempt, no wait' => [-1, $held, 1, $expired, []],
            'store failure: one attempt, no retry' => [
                5,
                static function (): bool {
                    throw new RuntimeException('store is down');
                },
                1,
                new RuntimeException('store is down'),
                [],
            ],
        ];
    }

    public function testCreateAcquiredHoldsTheLockUntilItIsReleased(): void
    {
        $factory = new LockFactory(new FlockStore());
        $lockManager = new LockManager($factory, 5);
        $noWait = new LockManager($factory, 1);
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
