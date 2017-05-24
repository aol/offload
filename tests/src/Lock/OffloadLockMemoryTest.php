<?php

namespace Aol\Offload\Lock;

class OffloadLockMemoryTest extends OffloadLockTest
{
    protected function setUp()
    {
        $this->lock = new OffloadLockMemory();
    }
}
