<?php

namespace Aol\Offload\Cache;

class OffloadCacheMemcachedTest extends OffloadCacheTest
{
    protected function setUp()
    {
        $client = new \Memcached();
        $client->addServer('localhost', 11211);
        $client->flush();
        $this->cache = new OffloadCacheMemcached($client);
    }
}
