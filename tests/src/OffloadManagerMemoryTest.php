<?php

namespace Aol\Offload;

use Aol\Offload\Cache\OffloadCacheMemory;
use Aol\Offload\Lock\OffloadLockMemory;

class OffloadManagerMemoryTest extends OffloadManagerTest
{
    protected function setUp()
    {
        $this->base_cache = new OffloadCacheMemory();
        $this->manager = new OffloadManager($this->base_cache, new OffloadLockMemory());
    }
}
