<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Service;

use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class LockManager
{
    private $lockFactory;
    private $ttl;

    public function __construct(
        LockFactory $lockFactory,
        int $ttl
    ) {
        $this->lockFactory = $lockFactory;
        $this->ttl = $ttl;
    }

    public function createLock(string $resource): LockInterface
    {
        return $this->lockFactory->createLock($resource, null);
    }

    public function acquire(LockInterface $lock): bool
    {
        foreach (range(1, $this->ttl) as $waited) {
            if ($lock->acquire()) {
                return true;
            }

            if ($waited >= $this->ttl) {
                throw new LockAcquiringException('Failed to acquire lock, wait time expired');
            }

            sleep(1);
        }

        throw new LockAcquiringException('Failed to acquire lock');
    }

    public function createAcquired(string $resource): LockInterface
    {
        $lock = $this->createLock($resource);
        $this->acquire($lock);

        return $lock;
    }

    public function release(LockInterface $lock): void
    {
        $lock->release();
    }
}
