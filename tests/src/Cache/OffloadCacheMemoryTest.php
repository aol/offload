<?php

namespace Aol\Offload\Cache;

class OffloadCacheMemoryTest extends OffloadCacheTest
{
    protected function setUp()
    {
        $this->cache = new OffloadCacheMemory();
    }
}
