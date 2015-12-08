<?php

namespace Aol\Offload\Tests;

use Aol\Offload\Cache\OffloadCacheRedis;
use Aol\Offload\Lock\OffloadLockRedis;
use Aol\Offload\OffloadManager;

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