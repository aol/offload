<?php

namespace Aol\Offload;

use Aol\Offload\Cache\OffloadCacheInterface;
use Aol\Offload\Deferred\OffloadDeferredComplete;
use Aol\Offload\Deferred\OffloadDeferredInterface;
use Aol\Offload\Lock\OffloadLockInterface;

class OffloadManager implements OffloadInterface
{
	/** @var OffloadCacheInterface The underlying cache. */
	protected $cache;
	/** @var OffloadLockInterface The lock. */
	protected $lock;
	/** @var callable[] An array of tasks to run on drain. */
	protected $tasks;
	/** @var array The default options for this offload manager. */
	protected $default_options;

	private static $static_default_options = [
		self::OPTION_TTL_FRESH          => 0.0,
		self::OPTION_TTL_STALE          => 5.0,
		self::OPTION_EXCLUSIVE          => true,
		self::OPTION_BACKGROUND         => true,
		self::OPTION_BACKGROUND_TIMEOUT => 5.0
	];

	/**
	 * Create a new offload manager.
	 *
	 * @param OffloadCacheInterface $cache The underlying cache to use.
	 * @param OffloadLockInterface  $lock  The lock to use.
	 */
	public function __construct(
		OffloadCacheInterface $cache,
		OffloadLockInterface $lock,
		array $default_options = []
	) {
		$this->cache           = $cache;
		$this->lock            = $lock;
		$this->default_options = $default_options + self::$static_default_options;
	}

	/**
	 * @inheritdoc
	 */
	public function fetch($key, callable $repopulate, $options = [])
	{
		$options = $options + $this->default_options;
		return $this->refresh($key, $repopulate, $options);
	}

	/**
	 * @inheritdoc
	 */
	public function fetchCached($key, $cache_ttl, callable $repopulate, $options = [])
	{
		$options = [self::OPTION_TTL_FRESH => $cache_ttl] + $options + $this->default_options;
		return $this->refresh($key, $repopulate, $options);
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
	public function queueCached($key, $cache_ttl, callable $task, $options = [])
	{
		$this->queue($key, $task, [self::OPTION_TTL_FRESH => $cache_ttl] + $options);
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
			return new OffloadResult(null, false, 0);
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
				return new OffloadResult(null, false, 0);
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
		$drained = [];
		$results = [];
		foreach ($this->tasks as $item) {
			list ($task, $key, $options) = $item;
			if ($options[self::OPTION_EXCLUSIVE]) {
				$results[] = [$key, $this->runLocked($key, $task, $options)];
			} else {
				$results[] = [$key, $this->run($key, $task, $options)];
			}
		}

		// Clear the task list.
		$this->tasks = [];

		// Wait for all deferreds to complete.
		foreach ($results as $item) {
			list ($key, $result) = $item;
			if ($result instanceof OffloadDeferredInterface) {
				$drained[$key] = $result->wait();
			}
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
		// Check cache as long as there is a cache time set.
		$cached = null;
		if ($options[self::OPTION_TTL_STALE] || $options[self::OPTION_TTL_FRESH]) {
			$cached = $this->cache->get($key);
		}

		$data  = null;
		$exp   = 0;
		$stale = false;
		if ($cached !== null) {
			list ($data, $exp) = $cached;
			$stale = time() >= $exp;
		}

		// If there is no data in cache or the data is stale and background fetch is turned off,
		// run the repopulate immediately and cache the results.
		if ($cached === null || ($stale && !$options[self::OPTION_BACKGROUND])) {
			$data = $this->run($key, $repopulate, $options)->wait();
			return new OffloadResult($data, false, 0);
		}

		// If there is data in cache and the data is stale, queue a background repopulate.
		if ($stale) {
			$this->queue($key, $repopulate, $options);
		}
		return new OffloadResult($data, true, $exp);
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
		$run  = new OffloadRun();
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
					$exp = time() + (int)$ttl_fresh;
					$ttl = $ttl_fresh + $ttl_stale;
					$this->cache->set($key, [$data, $exp], $ttl);
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
			$result->then(function () use ($token) {
				$this->lock->unlock($token);
			});
		}
		return $result;
	}
}
