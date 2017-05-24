<?php

namespace Aol\Offload;

class OffloadRun
{
    /** @var bool Whether the run returned a bad result. */
    protected $bad = false;
    /** @var array Additional cache options. */
    protected $cache_options = [];
    /** @var float|null Null values are ignored, number of seconds to cache value  */
    protected $ttl_fresh;
    /** @var float|null Null values are ignored, number of seconds set stale cache */
    protected $ttl_stale;

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

    /**
     * @return float|null The fresh TTL in seconds.
     */
    public function getTtlFresh()
    {
        return $this->ttl_fresh;
    }

    /**
     * @param float|null $ttl_fresh The fresh TTL in seconds.
     */
    public function setTtlFresh($ttl_fresh)
    {
        $this->ttl_fresh = $ttl_fresh;
    }

    /**
     * @return float|null The stale TTL in seconds.
     */
    public function getTtlStale()
    {
        return $this->ttl_stale;
    }

    /**
     * @param float|null $ttl_stale The stale TTL in seconds.
     */
    public function setTtlStale($ttl_stale)
    {
        $this->ttl_stale = $ttl_stale;
    }
}
