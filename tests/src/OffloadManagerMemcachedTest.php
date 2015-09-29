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
		$this->manager = new OffloadManager(new OffloadCacheMemcached($client), new OffloadLockMemcached($client));
	}
}