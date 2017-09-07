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
	 * @param string $key     The key to get.
	 * @param array  $options Additional custom options for the underlying cache.
	 *
	 * @return OffloadResult The offload result.
	 */
	public function get($key, array $options = []);

	/**
	 * Get many items from cache.
	 *
	 * @param array $keys    The keys to get.
	 * @param array $options Additional custom options for the underlying cache.
	 *
	 * @return OffloadResult[] Ordered offload results.
	 */
	public function getMany(array $keys, array $options = []);

	/**
	 * Delete items from cache.
	 *
	 * @param array $keys    The keys to delete.
	 * @param array $options Additional custom options for the underlying cache.
	 *
	 * @return bool Whether the delete was successful.
	 */
	public function delete(array $keys, array $options = []);

	/**
	 * Set an item in cache.
	 *
	 * @param string $key               The key to set.
	 * @param mixed  $data              The data to set.
	 * @param float  $ttl_fresh_seconds The fresh TTL in seconds.
	 * @param float  $ttl_stale_seconds The stale TTL in seconds.
	 * @param array  $options           Additional custom options for the underlying cache.
	 *
	 * @return bool Whether the set was successful.
	 */
	public function set($key, $data, $ttl_fresh_seconds, $ttl_stale_seconds, array $options = []);
}
