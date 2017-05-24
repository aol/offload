<?php

namespace Aol\Offload\Tests\Lock;

use Aol\Offload\Lock\OffloadLockMemcached;

class OffloadLockMemcachedTest extends OffloadLockTest
{
    protected function setUp()
    {
        $client = new \Memcached();
        $client->addServer('localhost', 11211);
        $client->flush();
        $this->lock = new OffloadLockMemcached($client);
    }
}
