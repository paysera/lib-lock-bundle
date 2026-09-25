<?php

declare(strict_types=1);

namespace Paysera\Bundle\LockBundle\Service;

use Paysera\Bundle\LockBundle\Test\Service\LockManagerTest;

function sleep(int $seconds): int
{
    LockManagerTest::$sleeps[] = $seconds;

    return 0;
}
