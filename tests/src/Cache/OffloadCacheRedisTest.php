<?php

namespace Aol\Offload\Cache;

class OffloadCacheRedisTest extends OffloadCacheTest
{
    protected function setUp()
    {
        $client = new \Predis\Client();
        $client->flushdb();
        $this->cache = new OffloadCacheRedis($client);
    }
}
