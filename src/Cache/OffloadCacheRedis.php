<?php

namespace Aol\Offload\Cache;

/**
 * Cache implementation for redis.
 */
class OffloadCacheRedis implements OffloadCacheInterface
{
    /** @var \Predis\Client The cache write connection. */
    private $write;
    /** @var \Predis\Client The cache read connection. */
    private $read;

    /**
     * Create a new offload cache.
     *
     * @param \Predis\Client      $write The write connection.
     * @param \Predis\Client|null $read  The read connection or null if same as write connection.
     */
    public function __construct(\Predis\Client $write, \Predis\Client $read = null)
    {
        $this->write = $write;
        $this->read  = $read ?: $write;
    }

    /**
     * @inheritdoc
     */
    public function get($key, array $options = [])
    {
        $command = $this->read->createCommand('GET', [$key]);
        $result  = $this->read->executeCommand($command);
        $value   = $this->unserialize($result);
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getMany(array $keys, array $options = [])
    {
        $command = $this->read->createCommand('MGET', $keys);
        $result  = $this->read->executeCommand($command);
        $values  = empty($result) ? array_fill(0, count($keys), null) : array_map([$this, 'unserialize'], $result);
        return $values;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $ttl_seconds, array $options = [])
    {
        $serialized = $this->serialize($value);
        $command    = $this->write->createCommand('SET', [$key, $serialized, 'PX', (int)(1000 * $ttl_seconds)]);
        $result     = $this->write->executeCommand($command);
        $ok         = $result === true || @strval($result) === 'OK';
        return $ok;
    }

    /**
     * @inheritdoc
     */
    public function delete(array $keys, array $options = [])
    {
        $command = $this->write->createCommand('DEL', $keys);
        $result  = $this->write->executeCommand($command);
        return (int)$result;
    }

    protected function serialize($object)
    {
        return serialize($object);
    }

    protected function unserialize($string)
    {
        return $string === null ? null : unserialize($string);
    }
}
