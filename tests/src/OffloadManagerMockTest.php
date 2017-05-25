<?php

namespace Aol\Offload;

use Aol\Offload\Lock\OffloadLockInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class OffloadManagerMockTest extends TestCase
{
    /** @var OffloadManager */
    private $offload_manager;

    /** @var OffloadManagerCache|MockObject */
    private $cache;

    /** @var OffloadLockInterface|MockObject */
    private $lock;

    protected function setUp()
    {
        $this->offload_manager = new OffloadManager(
            $this->getMock('Aol\Offload\Cache\OffloadCacheInterface'),
            $this->getMock('Aol\Offload\Lock\OffloadLockInterface')
        );

        // This value isn't injected, manually "injecting" it with reflection.
        $this->cache = $this->getMock('Aol\Offload\OffloadManagerCache', [], [], '', false);
        $cache = new \ReflectionProperty($this->offload_manager, 'cache');
        $cache->setAccessible(true);
        $cache->setValue($this->offload_manager, $this->cache);
    }

    public function testRunSetTtls()
    {
        // Force Cache Miss
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn(OffloadResult::miss());

        // Set expected cache value and ttls
        $this->cache->expects($this->once())
            ->method('set')
            ->with('my-key', ['hello' => 'world'], 14, 6, []);

        $this->offload_manager->fetchCached('my-key', 60, function (OffloadRun $run) {
            $run->setTtlFresh(14);
            $run->setTtlStale(6);

            return ['hello' => 'world'];
        });
    }
}
