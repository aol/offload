<?php

namespace Aol\Offload\Cache;

/**
 * Cache interface for result storage.
 */
interface OffloadCacheInterface
{
	/**
	 * Get the value from cache for the given key.
	 * Should return null for a cache miss.
	 *
	 * @param string $key The key to get.
	 *
	 * @return mixed|null The value or null if none is present in cache.
	 */
	function get($key);

	/**
	 * Get the values from cache for the given keys.
	 * Should return an array with values for given keys in the same order and null for a cache misses.
	 *
	 * @param string[] $keys The key to get.
	 *
	 * @return mixed[] The values or null if none is present in cache for all the given keys.
	 */
	function getMany(array $keys);

	/**
	 * Set the given key to the given value in the cache.
	 *
	 * @param string $key         The key to set.
	 * @param mixed  $value       The value to set the key to.
	 * @param float  $ttl_seconds The TTL for the key.
	 *
	 * @return bool Whether the set was successful.
	 */
	function set($key, $value, $ttl_seconds);


	/**
	 * Delete the given keys from cache.
	 *
	 * @param string[] $keys The keys to delete.
	 *
	 * @return bool Whether the delete was successful.
	 */
	function delete(array $keys);
}
