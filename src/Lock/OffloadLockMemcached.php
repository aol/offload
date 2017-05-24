<?php

namespace Aol\Offload\Lock;

/**
 * Lock implementation for memcached.
 */
class OffloadLockMemcached implements OffloadLockInterface
{
    /** @var \Memcached The cache connection. */
    private $client;

    public function __construct(\Memcached $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritdoc
     */
    public function lock($key, $timeout_seconds)
    {
        $ok = $this->client->add($key, '1', (int)$timeout_seconds);
        return $ok ? $key : null;
    }

    /**
     * @inheritdoc
     */
    public function unlock($token)
    {
        $ok = $this->client->delete($token);
        return !!$ok;
    }
}
