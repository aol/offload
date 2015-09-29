<?php

namespace Aol\Offload;

use Aol\Offload\Exceptions\OffloadDrainException;

interface OffloadInterface
{
	const OPTION_TTL_FRESH          = 'ttl_fresh';
	const OPTION_TTL_STALE          = 'ttl_stale';
	const OPTION_EXCLUSIVE          = 'exclusive';
	const OPTION_BACKGROUND         = 'background';
	const OPTION_BACKGROUND_TIMEOUT = 'background_timeout';

	/**
	 * Fetch a value from cache, if not present, offload a repopulate for the cache.
	 *
	 * @param string   $key        The key to fetch.
	 * @param callable $repopulate The repopulate callable.
	 * @param array    $options    Additional offload options.
	 *
	 * @return OffloadResult The offload result.
	 */
	function fetch($key, callable $repopulate, $options = []);

	/**
	 * Fetch a value from cache, if not present, offload a repopulate for the cache.
	 *
	 * @param string   $key        The key to fetch.
	 * @param float    $cache_ttl  How long to cache the value before repopulating.
	 * @param callable $repopulate The repopulate callable.
	 * @param array    $options    Additional offload options.
	 *
	 * @return OffloadResult The offload result.
	 */
	function fetchCached($key, $cache_ttl, callable $repopulate, $options = []);

	/**
	 * Queue a task to run when the offload manager is drained.
	 * By default the task result will not be cached unless a TTL is specified in the options.
	 *
	 * @param string   $key     A key for the task.
	 * @param callable $task    The task to run.
	 * @param array    $options Options for the task.
	 */
	function queue($key, callable $task, $options = []);

	/**
	 * Queue a task to run when the offload manager is drained.
	 * Cache the task result in the given key.
	 *
	 * @param string   $key       A key for the task.
	 * @param float    $cache_ttl How long to cache the task result for.
	 * @param callable $task      The task to run.
	 * @param array    $options   Options for the task.
	 */
	function queueCached($key, $cache_ttl, callable $task, $options = []);

	/**
	 * Check to see if a value is in the offload cache.
	 *
	 * @param string $key The key to check.
	 *
	 * @return OffloadResult The offload result.
	 */
	function get($key);

	/**
	 * Check to see if values are in the offload cache.
	 *
	 * @param string[] $keys The ordered keys to check.
	 *
	 * @return OffloadResult[] The ordered offload results.
	 */
	function getMany(array $keys);

	/**
	 * Delete the given keys from cache.
	 *
	 * @param string[] $keys The keys to delete.
	 *
	 * @return int The number of keys deleted.
	 */
	function delete(array $keys);

	/**
	 * @return bool Whether this offload manager has queued work.
	 */
	function hasWork();

	/**
	 * Drain all work in this offload manager.
	 *
	 * @return array The keys that were drained. A map of key to result.
	 *
	 * @throws OffloadDrainException When there were errors draining results.
	 */
	function drain();
}
