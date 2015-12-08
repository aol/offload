<?php

namespace Aol\Offload\Tests;

use Aol\Offload\Cache\OffloadCacheMemcached;
use Aol\Offload\Lock\OffloadLockMemcached;
use Aol\Offload\OffloadManager;

class OffloadManagerMemcachedTest extends OffloadManagerTest
{
	protected function setUp()
	{
		$client = new \Memcached();
		$client->addServer('localhost', 11211);
		$client->flush();
		$this->base_cache = new OffloadCacheMemcached($client);
		$this->manager = new OffloadManager($this->base_cache, new OffloadLockMemcached($client));
	}
}