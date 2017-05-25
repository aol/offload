<?php

namespace Aol\Offload;

use Aol\Offload\Deferred\OffloadDeferredComplete;
use Aol\Offload\Deferred\OffloadDeferredInterface;

class OffloadResult
{
    /** @var mixed The data. */
    protected $data;
    /** @var OffloadDeferredInterface|null */
    protected $deferred;
    /** @var bool Whether the data came from cache. */
    protected $from_cache;
    /** @var int When the data expires. */
    protected $expires;
    /** @var OffloadResult A cache miss. */
    private static $miss;

    /**
     * Create a new offload result.
     *
     * @param mixed $data       The data.
     * @param bool  $from_cache Whether the data came from cache.
     * @param int   $expires    When the data expires.
     */
    public function __construct($data, $from_cache, $expires)
    {
        if ($data instanceof OffloadDeferredInterface) {
            $this->deferred = $data;
        } else {
            $this->data = $data;
        }
        $this->from_cache = $from_cache;
        $this->expires    = $expires;
    }

    /**
     * @return OffloadDeferredInterface
     */
    public function getDeferred()
    {
        if (!$this->deferred instanceof OffloadDeferredInterface) {
            $this->deferred = new OffloadDeferredComplete($this->data);
        }

        return $this->deferred;
    }

    /**
     * @return mixed The data.
     */
    public function getData()
    {
        if ($this->data === null && $this->deferred instanceof OffloadDeferredInterface) {
            $this->data = $this->deferred->wait();
        }

        return $this->data;
    }

    /**
     * @return bool Whether the data came from cache.
     */
    public function isFromCache()
    {
        return $this->from_cache;
    }

    /**
     * @return int The unix expire time in seconds.
     */
    public function getExpireTime()
    {
        return $this->expires;
    }

    /**
     * @return int How long the data has been stale in seconds.
     * If the value is less than zero, that's how far it is from becoming stale.
     */
    public function getStaleTime()
    {
        return $this->from_cache ? time() - $this->expires : 0;
    }

    /**
     * @return bool Whether the result is stale.
     */
    public function isStale()
    {
        return $this->from_cache && $this->getStaleTime() >= 0;
    }

    /**
     * @return OffloadResult A cache miss.
     */
    public static function miss()
    {
        return self::$miss ?: (self::$miss = new OffloadResult(null, false, 0));
    }
}
