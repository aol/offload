<?php

namespace Aol\Offload\Tests\Cache;

use Aol\Offload\Cache\OffloadCacheInterface;

abstract class OffloadCacheTest extends \PHPUnit_Framework_TestCase
{
	/** @var OffloadCacheInterface */
	protected $cache;

	public function testNoValue()
	{
		$this->assertNull($this->cache->get(__METHOD__));
	}

	/**
	 * @dataProvider cacheValues
	 */
	public function testCache($key, $value)
	{
		$key = __METHOD__ . $key;
		$this->assertTrue($this->cache->set($key, $value, 5));
		$this->assertEquals($value, $this->cache->get($key));
	}

	public function cacheValues()
	{
		return [
			['1','foo'],
			['2',1],
			['3',false],
			['4',new \DateTime],
			['5',['x'=>1,'foo'=>['bar','baz',['qux']]]],
			['6',[false]],
			['7',new \stdClass()],
			['8',384234.2333]
		];
	}
}
