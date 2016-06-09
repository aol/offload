<?php

namespace Aol\Offload\Lock;

/**
 * In-memory lock implementation for testing.
 */
class OffloadLockMemory implements OffloadLockInterface
{
    /** @var array The current locks. */
    private $locks = [];

    /**
     * @inheritdoc
     */
    public function lock($key, $timeout_seconds)
    {
        $expire = isset($this->locks[$key]) ? $this->locks[$key] : 0;
        if ($expire <= time()) {
            $this->locks[$key] = time() + $timeout_seconds;
            return $key;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function unlock($token)
    {
        if (isset($this->locks[$token])) {
            unset($this->locks[$token]);
            return true;
        }
        return false;
    }
}
