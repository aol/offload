<?php

namespace Aol\Offload;

use Aol\Offload\Cache\OffloadCacheInterface;

/**
 * Offload manager cache wrapping a cache implementation
 */
class OffloadManagerCache implements OffloadManagerCacheInterface
{
	/** @var OffloadCacheInterface The underlying cache. */
	protected $cache;

	/**
	 * Create a new offload manager.
	 *
	 * @param OffloadCacheInterface $cache The underlying cache to use.
	 */
	public function __construct(OffloadCacheInterface $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * @inheritdoc
	 */
	public function get($key)
	{
		$cached = $this->cache->get($key);
		if ($cached !== null) {
			list ($data, $exp) = $cached;
			return new OffloadResult($data, true, $exp);
		} else {
			return OffloadResult::miss();
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getMany(array $keys)
	{
		$cached = $this->cache->getMany($keys);
		return array_map(function ($cached) {
			if ($cached !== null) {
				list ($data, $exp) = $cached;
				return new OffloadResult($data, true, $exp);
			} else {
				return OffloadResult::miss();
			}
		}, $cached);
	}

	/**
	 * @inheritdoc
	 */
	public function delete(array $keys)
	{
		return $this->cache->delete($keys);
	}

	/**
	 * @inheritdoc
	 */
	public function set($key, $data, $ttl_fresh_seconds, $ttl_stale_seconds)
	{
		$exp = time() + (int)$ttl_fresh_seconds;
		$ttl = $ttl_fresh_seconds + $ttl_stale_seconds;
		return $this->cache->set($key, [$data, $exp], $ttl);
	}
}
