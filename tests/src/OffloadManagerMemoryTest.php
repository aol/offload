<?php

namespace Aol\Offload\Tests;

use Aol\Offload\Cache\OffloadCacheMemory;
use Aol\Offload\Lock\OffloadLockMemory;
use Aol\Offload\OffloadManager;

class OffloadManagerMemoryTest extends OffloadManagerTest
{
    protected function setUp()
    {
        $this->base_cache = new OffloadCacheMemory();
        $this->manager = new OffloadManager($this->base_cache, new OffloadLockMemory());
    }
}