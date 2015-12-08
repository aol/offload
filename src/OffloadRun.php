<?php

namespace Aol\Offload;

class OffloadRun
{
	/** @var bool Whether the run returned a bad result. */
	protected $bad = false;
	/** @var array Additional cache options. */
	protected $cache_options = [];

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
	 * @param array $cache_options Additional cache options.
	 */
	public function setCacheOptions(array $cache_options)
	{
		$this->cache_options = $cache_options;
	}

	/**
	 * @return bool Whether the run returned a bad result.
	 */
	public function isBad()
	{
		return $this->bad;
	}

	/**
	 * @return array The cache options for this run.
	 */
	public function getCacheOptions()
	{
		return $this->cache_options;
	}
}
