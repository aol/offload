<?php

namespace Aol\Offload\Lock;

/**
 * Make locking a no-op.
 */
class OffloadLockBypass implements OffloadLockInterface
{
	/**
	 * @inheritdoc
	 */
	public function lock($key, $timeout_seconds)
	{
		return 'bypass';
	}

	/**
	 * @inheritdoc
	 */
	public function unlock($token)
	{
		return true;
	}
}
