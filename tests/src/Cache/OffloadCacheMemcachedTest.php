<?php

namespace Aol\Offload\Tests\Cache;

use Aol\Offload\Cache\OffloadCacheMemcached;

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
