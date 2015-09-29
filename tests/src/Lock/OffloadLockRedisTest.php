<?php

namespace Aol\Offload\Tests\Lock;

use Aol\Offload\Lock\OffloadLockRedis;

class OffloadLockRedisTest extends OffloadLockTest
{
	protected function setUp()
	{
		$client = new \Predis\Client();
		$client->flushdb();
		$this->lock = new OffloadLockRedis($client);
	}
}