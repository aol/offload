<?php

namespace Aol\Offload\Deferred;

/**
 * Represents an async task that can be waited on.
 */
final class OffloadDeferred implements OffloadDeferredInterface
{
	/** @var callable The waiter callable. */
	private $waiter;
	/** @var bool Whether the waiter was already called. */
	private $waited = false;
	/** @var mixed The result from the waiter. */
	private $result;
	/** @var callable[] Completion listeners. */
	private $listeners = [];

	/**
	 * Create a new deferred.
	 *
	 * @param callable $waiter A callable that will wait.
	 */
	public function __construct(callable $waiter)
	{
		$this->waiter = $waiter;
	}

	/**
	 * @inheritdoc
	 */
	public function wait()
	{
		if (!$this->waited) {
			$waiter = $this->waiter;
			$this->result = $waiter();
			$this->waited = true;
			foreach ($this->listeners as $fulfilled) {
				$fulfilled($this->result);
			}
			$this->listeners = [];
		}
		return $this->result;
	}

	/**
	 * @inheritdoc
	 */
	public function then(callable $fulfilled)
	{
		if ($this->waited) {
			$fulfilled($this->result);
		} else {
			$this->listeners[] = $fulfilled;
		}
	}
}
