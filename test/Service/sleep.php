<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Service;

use Paysera\Bundle\LockBundle\Test\Service\LockManagerTest;

/**
 * LockManager calls sleep() without a namespace, so PHP finds this function first while the tests run:
 * the tests count the waits instead of sleeping through them.
 */
function sleep(int $seconds): int
{
    LockManagerTest::$sleeps[] = $seconds;

    return 0;
}
