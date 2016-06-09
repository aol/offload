<?php

namespace Aol\Offload\Tests\Cache;

use Aol\Offload\Cache\OffloadCacheMemory;

class OffloadCacheMemoryTest extends OffloadCacheTest
{
    protected function setUp()
    {
        $this->cache = new OffloadCacheMemory();
    }
}
