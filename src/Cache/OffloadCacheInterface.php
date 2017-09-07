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
     * @param string $key     The key to get.
     * @param array  $options Additional custom options.
     *
     * @return string The value or null if none is present in cache.
     */
    public function get($key, array $options = []);

    /**
     * Get the values from cache for the given keys.
     * Should return an array with values for given keys in the same order and null for a cache misses.
     *
     * @param string[] $keys    The key to get.
     * @param array    $options Additional custom options.
     *
     * @return string[] The values or null if none is present in cache for all the given keys.
     */
    public function getMany(array $keys, array $options = []);

    /**
     * Set the given key to the given value in the cache.
     *
     * @param string $key         The key to set.
     * @param string $value       The value to set the key to.
     * @param float  $ttl_seconds The TTL for the key.
     * @param array  $options     Additional custom options.
     *
     * @return bool Whether the set was successful.
     */
    public function set($key, $value, $ttl_seconds, array $options = []);


    /**
     * Delete the given keys from cache.
     *
     * @param string[] $keys    The keys to delete.
     * @param array    $options Additional custom options.
     *
     * @return int The number of keys deleted.
     */
    public function delete(array $keys, array $options = []);
}
