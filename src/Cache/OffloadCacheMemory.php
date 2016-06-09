<?php

namespace Aol\Offload\Cache;

/**
 * In-memory cache implementation for testing.
 */
class OffloadCacheMemory implements OffloadCacheInterface
{
    /** @var array The current cache data. */
    private $data = [];

    /**
     * @inheritdoc
     */
    public function get($key, array $options = [])
    {
        $item = isset($this->data[$key]) ? $this->data[$key] : [null, null];
        list ($value, $expire) = $item;
        if ($expire && $expire <= time()) {
            $value = null;
            unset($this->data[$key]);
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getMany(array $keys, array $options = [])
    {
        $values = [];
        foreach ($keys as $key) {
            $values[] = $this->get($key, $options);
        }
        return $values;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $ttl_seconds, array $options = [])
    {
        $this->data[$key] = [$value, time() + $ttl_seconds];
        return true;
    }

    /**
     * @inheritdoc
     */
    public function delete(array $keys, array $options = [])
    {
        $deleted = 0;
        foreach ($keys as $key) {
            if (isset($this->data[$key])) {
                unset($this->data[$key]);
                $deleted++;
            }
        }
        return $deleted;
    }
}
