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
	public function get($key, array $options = [])
	{
		$cached = $this->cache->get($key, $options);
		if (is_array($cached) && count($cached) === 2) {
			list ($data, $exp) = $cached;
			return new OffloadResult($data, true, $exp);
		} else {
			return OffloadResult::miss();
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getMany(array $keys, array $options = [])
	{
		$cached = $this->cache->getMany($keys, $options);
		return array_map(function ($cached) {
			if (is_array($cached) && count($cached) === 2) {
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
	public function delete(array $keys, array $options = [])
	{
		return $this->cache->delete($keys, $options);
	}

	/**
	 * @inheritdoc
	 */
	public function set($key, $data, $ttl_fresh_seconds, $ttl_stale_seconds, array $options = [])
	{
		$exp = time() + (int)$ttl_fresh_seconds;
		$ttl = $ttl_fresh_seconds + $ttl_stale_seconds;
		return $this->cache->set($key, [$data, $exp], $ttl, $options);
	}

	/**
	 * @return OffloadCacheInterface The underlying cache being used.
	 */
	public function getBaseCache()
	{
		return $this->cache;
	}
}
