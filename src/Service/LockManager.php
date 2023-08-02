<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Service;

use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class LockManager
{
    private $lockFactory;
    private $ttw;
    private $ttl;

    public function __construct(
        LockFactory $lockFactory,
        int $ttw,
        int $ttl = null
    ) {
        $this->lockFactory = $lockFactory;
        $this->ttw = $ttw;
        $this->ttl = $ttl;
    }

    public function createLock(string $resource): LockInterface
    {
        return $this->lockFactory->createLock($resource, $this->ttl);
    }

    public function acquire(LockInterface $lock): bool
    {
        foreach (range(1, $this->ttw) as $waited) {
            if ($lock->acquire()) {
                return true;
            }

            if ($waited >= $this->ttw) {
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

    public function release(LockInterface $lock)
    {
        $lock->release();
    }
}
