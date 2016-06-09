<?php

namespace Aol\Offload\Tests\Lock;

use Aol\Offload\Lock\OffloadLockMemory;

class OffloadLockMemoryTest extends OffloadLockTest
{
    protected function setUp()
    {
        $this->lock = new OffloadLockMemory();
    }
}
