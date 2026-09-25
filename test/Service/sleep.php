<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Service;

use Paysera\Bundle\LockBundle\Test\Service\LockManagerTest;

/**
 * LockManager calls sleep() without a namespace, so PHP finds this function first while the tests run:
 * the tests count the waits instead of sleeping through them. composer.json loads this file through autoload-dev
 * "files", before any test runs: PHP binds each call to the first function it finds, so a later definition
 * would not be seen.
 */
function sleep(int $seconds): int
{
    LockManagerTest::$sleeps[] = $seconds;

    return 0;
}
