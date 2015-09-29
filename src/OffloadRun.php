<?php

namespace Aol\Offload;

class OffloadRun
{
	/** @var bool Whether the run returned a bad result. */
	protected $bad = false;

	/**
	 * Set the result to be bad (meaning it won't get cached).
	 *
	 * @param bool $bad Whether the result is bad.
	 */
	public function setBad($bad = true)
	{
		$this->bad = !!$bad;
	}

	/**
	 * @return bool Whether the run returned a bad result.
	 */
	public function isBad()
	{
		return $this->bad;
	}
}
