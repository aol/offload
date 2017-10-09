<?php

namespace Aol\Offload;

use Aol\Offload\Exceptions\OffloadDrainException;

interface OffloadManagerInterface
{
    const OPTION_TTL_FRESH               = 'ttl_fresh';
    const OPTION_TTL_STALE               = 'ttl_stale';
    const OPTION_EXCLUSIVE               = 'exclusive';
    const OPTION_BACKGROUND              = 'background';
    const OPTION_BACKGROUND_TIMEOUT      = 'background_timeout';
    const OPTION_BACKGROUND_RELEASE_LOCK = 'background_release_lock';
    const OPTION_CACHE_OPTIONS           = 'cache_options';
    const OPTION_NAMESPACE               = 'namespace';
    const OPTION_FORCE                   = 'force';

    /**
     * Fetch a value from cache, if not present, offload a repopulate for the cache.
     *
     * @param string   $key        The key to fetch.
     * @param callable $repopulate The repopulate callable.
     * @param array    $options    Additional offload options.
     *
     * @return OffloadResult The offload result.
     */
    public function fetch($key, callable $repopulate, $options = []);

    /**
     * Fetch a value from cache, if not present, offload a repopulate for the cache.
     *
     * @param string   $key        The key to fetch.
     * @param float    $ttl_fresh  How long to cache the value before repopulating.
     * @param callable $repopulate The repopulate callable.
     * @param array    $options    Additional offload options.
     *
     * @return OffloadResult The offload result.
     */
    public function fetchCached($key, $ttl_fresh, callable $repopulate, $options = []);

    /**
     * Queue a task to run when the offload manager is drained.
     * By default the task result will not be cached unless a TTL is specified in the options.
     *
     * @param string   $key     A key for the task.
     * @param callable $task    The task to run.
     * @param array    $options Options for the task.
     */
    public function queue($key, callable $task, $options = []);

    /**
     * Queue a task to run when the offload manager is drained.
     * Cache the task result in the given key.
     *
     * @param string   $key       A key for the task.
     * @param float    $ttl_fresh How long to cache the task result for.
     * @param callable $task      The task to run.
     * @param array    $options   Options for the task.
     */
    public function queueCached($key, $ttl_fresh, callable $task, $options = []);

    /**
     * Get the offload manager cache.
     *
     * @return OffloadManagerCacheInterface The offload manager cache.
     */
    public function getCache();

    /**
     * @return bool Whether this offload manager has queued work.
     */
    public function hasWork();

    /**
     * Drain all work in this offload manager.
     *
     * @return array The keys that were drained. A map of key to result.
     *
     * @throws OffloadDrainException When there were errors draining results.
     */
    public function drain();
}
