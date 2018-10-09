<?php

namespace Paysera\Bundle\LockBundle\Service;

use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Lock;

class LockManager
{
    private $lockFactory;
    private $ttl;

    public function __construct(
        Factory $lockFactory,
        int $ttl
    ) {
        $this->lockFactory = $lockFactory;
        $this->ttl = $ttl;
    }

    public function createLock(string $resource): Lock
    {
        return $this->lockFactory->createLock($resource, $this->ttl);
    }

    public function acquire(Lock $lock): bool
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

    public function createAcquired(string $resource): Lock
    {
        $lock = $this->createLock($resource);
        $this->acquire($lock);

        return $lock;
    }

    public function release(Lock $lock)
    {
        $lock->release();
    }
}
