<?php

namespace Aol\Offload;

use Aol\Offload\Cache\OffloadCacheInterface;
use Aol\Offload\Deferred\OffloadDeferredComplete;
use Aol\Offload\Deferred\OffloadDeferredInterface;
use Aol\Offload\Exceptions\OffloadDrainException;
use Aol\Offload\Lock\OffloadLockInterface;

class OffloadManager implements OffloadManagerInterface
{
    /** @var OffloadManagerCacheInterface The offload manager cache. */
    protected $cache;
    /** @var OffloadLockInterface The lock. */
    protected $lock;
    /** @var callable[] An array of tasks to run on drain. */
    protected $tasks = [];
    /** @var array The default options for this offload manager. */
    protected $default_options;

    /** @var array Default options for all offload manager instances. */
    private static $static_options = [
        self::OPTION_TTL_FRESH               => 0.0,
        self::OPTION_TTL_STALE               => 5.0,
        self::OPTION_EXCLUSIVE               => true,
        self::OPTION_BACKGROUND              => true,
        self::OPTION_BACKGROUND_TIMEOUT      => 5.0,
        self::OPTION_BACKGROUND_RELEASE_LOCK => true,
        self::OPTION_CACHE_OPTIONS           => [],
        self::OPTION_NAMESPACE               => '',
        self::OPTION_FORCE                   => false,
    ];

    /**
     * Create a new offload manager.
     *
     * @param OffloadCacheInterface $cache           The underlying cache to use.
     * @param OffloadLockInterface  $lock            The lock to use.
     * @param array                 $default_options The default options for this offload manager.
     */
    public function __construct(
        OffloadCacheInterface $cache,
        OffloadLockInterface $lock,
        array $default_options = []
    ) {
        $this->default_options = $default_options + self::$static_options;
        $this->cache           = new OffloadManagerCache($cache, $this->default_options[self::OPTION_NAMESPACE]);
        $this->lock            = $lock;
    }

    /**
     * @inheritdoc
     */
    public function fetch($key, callable $repopulate, $options = [])
    {
        return $this->refresh($key, $repopulate, $options + $this->default_options);
    }

    /**
     * @inheritdoc
     */
    public function fetchCached($key, $ttl_fresh, callable $repopulate, $options = [])
    {
        return $this->fetch($key, $repopulate, [self::OPTION_TTL_FRESH => $ttl_fresh] + $options);
    }

    /**
     * @inheritdoc
     */
    public function queue($key, callable $task, $options = [])
    {
        $options = $options + [self::OPTION_TTL_FRESH => 0.0, self::OPTION_TTL_STALE => 0.0] + $this->default_options;
        if ($options[self::OPTION_EXCLUSIVE]) {
            $this->tasks[$key] = [$task, $key, $options];
        } else {
            $this->tasks[] = [$task, $key, $options];
        }
    }

    /**
     * @inheritdoc
     */
    public function queueCached($key, $ttl_fresh, callable $task, $options = [])
    {
        $this->queue($key, $task, [self::OPTION_TTL_FRESH => $ttl_fresh] + $options);
    }

    /**
     * @inheritdoc
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @inheritdoc
     */
    public function hasWork()
    {
        return count($this->tasks) > 0;
    }

    /**
     * @inheritdoc
     */
    public function drain()
    {
        // Drain the task list. Run each task.
        $drained    = [];
        $exceptions = [];
        $results    = [];
        foreach ($this->tasks as $item) {
            list ($task, $key, $options) = $item;
            try {
                if ($options[self::OPTION_EXCLUSIVE]) {
                    $result = $this->runLocked($key, $task, $options);
                } else {
                    $result = $this->run($key, $task, $options);
                }
                $results[] = [$key, $result];
            } catch (\Exception $exception) {
                $exceptions[$key] = $exception;
            }
        }

        // Clear the task list.
        $this->tasks = [];

        // Wait for all deferreds to complete.
        foreach ($results as $item) {
            list ($key, $result) = $item;
            if ($result instanceof OffloadDeferredInterface) {
                try {
                    $drained[$key] = $result->wait();
                } catch (\Exception $exception) {
                    $exceptions[$key] = $exception;
                }
            }
        }

        if (!empty($exceptions)) {
            throw new OffloadDrainException(get_class() . ': could_not_drain_all_tasks', $exceptions, $drained);
        }

        return $drained;
    }

    /**
     * Get the given key and refresh it if not found in cache or is stale.
     *
     * @param string   $key        The key to get/refresh.
     * @param callable $repopulate A callable that returns fresh data for the key.
     * @param array    $options    Offload options.
     *
     * @return OffloadResult The offload result.
     */
    protected function refresh($key, callable $repopulate, $options = [])
    {
        // Check cache as long as the refresh isn't forced and there is a cache time set.
        if (!$options[self::OPTION_FORCE] && ($options[self::OPTION_TTL_STALE] || $options[self::OPTION_TTL_FRESH])) {
            $cache_options = empty($options[self::OPTION_CACHE_OPTIONS]) ? [] : $options[self::OPTION_CACHE_OPTIONS];
            $result = $this->cache->get($key, $cache_options);
        } else {
            $result = OffloadResult::miss();
        }

        $from_cache = $result->isFromCache();
        $stale      = $result->isStale();

        if (!$from_cache || ($stale && !$options[self::OPTION_BACKGROUND])) {

            // If there is no data in cache or the data is stale and background fetch is turned off,
            // run the repopulate immediately and cache the results.
            $data = $this->run($key, $repopulate, $options);
            $result = new OffloadResult($data, false, 0);

        } elseif ($stale) {

            // If there is data in cache and the data is stale, queue a background repopulate.
            $this->queue($key, $repopulate, $options);
        }

        return $result;
    }

    /**
     * Run the given task and return a deferred resolving to the data it returns.
     *
     * @param string   $key     The key for the task.
     * @param callable $task    The task to run.
     * @param array    $options Offload options.
     *
     * @return OffloadDeferredInterface The deferred for the task result.
     */
    protected function run($key, $task, $options)
    {
        $run = new OffloadRun();
        $run->setCacheOptions(empty($options[self::OPTION_CACHE_OPTIONS]) ? [] : $options[self::OPTION_CACHE_OPTIONS]);
        $data = $task($run);
        if ($data instanceof OffloadDeferredInterface) {
            $result = $data;
        } else {
            $result = new OffloadDeferredComplete($data);
        }

        // When the task completes, cache the result.
        $ttl_fresh = $options[self::OPTION_TTL_FRESH];
        $ttl_stale = $options[self::OPTION_TTL_STALE];
        if ($ttl_fresh || $ttl_stale) {
            $result->then(function ($data) use ($key, $run, $ttl_fresh, $ttl_stale) {
                if (!$run->isBad()) {
                    $run_ttl_fresh = $run->getTtlFresh();
                    if ($run_ttl_fresh !== null) {
                        $ttl_fresh = $run_ttl_fresh;
                    }

                    $run_ttl_stale = $run->getTtlStale();
                    if ($run_ttl_stale !== null) {
                        $ttl_stale = $run_ttl_stale;
                    }

                    $this->cache->set($key, $data, $ttl_fresh, $ttl_stale, $run->getCacheOptions());
                }
            });
        }

        return $result;
    }

    /**
     * Run the given task and return a deferred resolving to the data it returns.
     * If the key could not be locked, null is returned.
     *
     * @param string   $key     The key to lock for the task.
     * @param callable $task    The task to run.
     * @param array    $options Offload options.
     *
     * @return OffloadDeferredInterface|null The deferred for the task result or null if the key couldn't be locked.
     */
    protected function runLocked($key, $task, $options)
    {
        $result = null;
        $token  = $this->lock->lock("$key:lock", $options[self::OPTION_BACKGROUND_TIMEOUT]);
        if ($token !== null) {
            $result = $this->run($key, $task, $options);
            if ($options[self::OPTION_BACKGROUND_RELEASE_LOCK]) {
                $result->then(function () use ($token) {
                    $this->lock->unlock($token);
                });
            }
        }
        return $result;
    }
}
