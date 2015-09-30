<?php

namespace Aol\Offload;

/**
 * For cache interactions that return an OffloadResult.
 */
interface OffloadManagerCacheInterface
{
	/**
	 * Get an item from cache.
	 *
	 * @param string $key The key to get.
	 *
	 * @return OffloadResult The offload result.
	 */
	function get($key);

	/**
	 * Get many items from cache.
	 *
	 * @param array $keys The keys to get.
	 *
	 * @return OffloadResult[] Ordered offload results.
	 */
	function getMany(array $keys);

	/**
	 * Delete items from cache.
	 *
	 * @param array $keys The keys to delete.
	 *
	 * @return bool Whether the delete was successful.
	 */
	function delete(array $keys);

	/**
	 * Set an item in cache.
	 *
	 * @param string $key               The key to set.
	 * @param mixed  $data              The data to set.
	 * @param float  $ttl_fresh_seconds The fresh TTL in seconds.
	 * @param float  $ttl_stale_seconds The stale TTL in seconds.
	 *
	 * @return bool Whether the set was successful.
	 */
	function set($key, $data, $ttl_fresh_seconds, $ttl_stale_seconds);
}
