<?php

namespace Aol\Offload\Tests\Lock;

use Aol\Offload\Lock\OffloadLockInterface;

abstract class OffloadLockTest extends \PHPUnit_Framework_TestCase
{
	/** @var OffloadLockInterface */
	protected $lock;

	public function testLock()
	{
		$this->assertNotNull($token = $this->lock->lock(__METHOD__, 10));
		$this->assertNull($this->lock->lock(__METHOD__, 10));
		$this->assertFalse($this->lock->unlock(__METHOD__ . 'x'));
		$this->assertTrue($this->lock->unlock($token));
	}
}
