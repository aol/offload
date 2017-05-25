<?php

namespace Aol\Offload\Lock;

class OffloadLockRedisTest extends OffloadLockTest
{
    protected function setUp()
    {
        $client = new \Predis\Client();
        $client->flushdb();
        $this->lock = new OffloadLockRedis($client);
    }
}
