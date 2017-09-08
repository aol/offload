<?php

namespace Aol\Offload\Lock;

/**
 * Lock implementation for redis.
 */
class OffloadLockRedis implements OffloadLockInterface
{
    /** @var \Predis\Client The cache connection. */
    private $client;

    public function __construct(\Predis\Client $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritdoc
     */
    public function lock($key, $timeout_seconds)
    {
        $result = $this->client->set($key, '1', 'PX', (int)(1000 * $timeout_seconds), 'NX');
        $ok     = $result === true || (string)$result === 'OK';
        return $ok ? $key : null;
    }

    /**
     * @inheritdoc
     */
    public function unlock($token)
    {
        $result = $this->client->del($token);
        return $result > 0;
    }
}
