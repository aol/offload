<?php

namespace Aol\Offload;

use Aol\Offload\Cache\OffloadCacheRedis;
use Aol\Offload\Lock\OffloadLockRedis;

class OffloadManagerRedisTest extends OffloadManagerTest
{
    protected function setUp()
    {
        $client = new \Predis\Client();
        $client->flushdb();
        $this->base_cache = new OffloadCacheRedis($client);
        $this->manager = new OffloadManager($this->base_cache, new OffloadLockRedis($client));
    }
}
