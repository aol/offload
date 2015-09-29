<?php

namespace Aol\Offload\Exceptions;

/**
 * An aggregate exception when one more more exceptions are thrown during
 * the offload manager task queue drain.
 */
class OffloadDrainException extends \RuntimeException
{
	/** @var \Exception[] All exceptions that occurred during the drain. */
	protected $exceptions;
	/** @var array Drained results by key. */
	protected $drained;

	/**
	 * Create a new drain exception.
	 *
	 * @param string       $message    The message
	 * @param int          $code       The code.
	 * @param \Exception[] $exceptions All exceptions that occurred during the drain
	 * @param array        $drained    Drained results by key.
	 */
	public function __construct($message = "", array $exceptions = [], array $drained = [])
	{
		$this->exceptions = $exceptions;
		$this->drained    = $drained;

		$previous = null;
		if (!empty($exceptions)) {
			end($exceptions);
			$previous = $exceptions[key($exceptions)];
			reset($exceptions);
		}
		parent::__construct($message, 0, $previous);
	}

	/**
	 * @return array The successfully drained results by key.
	 */
	public function getDrainedResults()
	{
		return $this->drained;
	}

	/**
	 * @return \Exception[] The exceptions that were raised.
	 */
	public function getDrainedExceptions()
	{
		return $this->exceptions;
	}
}