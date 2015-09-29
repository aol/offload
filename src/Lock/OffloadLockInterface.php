<?php

namespace Aol\Offload\Lock;

/**
 * Lock interface for running tasks exclusively.
 */
interface OffloadLockInterface
{
	/**
	 * Lock the given key for the given amount of seconds.
	 *
	 * @param string $key             The key to lock.
	 * @param float  $timeout_seconds The number of seconds to lock the key for.
	 *
	 * @return mixed|null An unlock token or null if the key could not be locked.
	 */
	function lock($key, $timeout_seconds);

	/**
	 * Unlock the given key.
	 *
	 * @param mixed $token The unlock token returned from lock.
	 *
	 * @return bool Whether the key was unlocked.
	 */
	function unlock($token);
}
