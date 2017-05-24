<?php

namespace Aol\Offload\Lock;

class OffloadLockBypassTest extends \PHPUnit_Framework_TestCase
{
    /** @var OffloadLockBypass */
    protected $lock;

    protected function setUp()
    {
        $this->lock = new OffloadLockBypass();
    }

    public function testLock()
    {
        $this->assertNotNull($token = $this->lock->lock(__METHOD__, 10));
        $this->assertNotNull($this->lock->lock(__METHOD__, 10));
        $this->assertTrue($this->lock->unlock(__METHOD__ . 'x'));
        $this->assertTrue($this->lock->unlock($token));
    }
}
