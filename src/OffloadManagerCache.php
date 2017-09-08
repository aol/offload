<?php

namespace Aol\Offload;

use Aol\Offload\Cache\OffloadCacheInterface;
use Aol\Offload\Encoders\OffloadEncoderInterface;
use Aol\Offload\Encoders\OffloadEncoderStandard;

/**
 * Offload manager cache wrapping a cache implementation
 */
class OffloadManagerCache implements OffloadManagerCacheInterface
{
    /** @var OffloadCacheInterface The underlying cache. */
    private $cache;
    /** @var OffloadEncoderInterface The encoder to use (default to standard). */
    private $encoder;
    /** @var OffloadEncoderInterface The decoder to use (default to the encoder). */
    private $decoder;
    /** @var string The namespace to use in cache key. */
    private $namespace;

    /**
     * Create a new offload manager.
     *
     * @param OffloadCacheInterface $cache     The underlying cache to use.
     * @param string                $namespace The namespace to use in cache key.
     */
    public function __construct(OffloadCacheInterface $cache, $namespace = '')
    {
        $this->cache = $cache;
        $this->namespace = $namespace;
    }

    /**
     * Get the encoder.
     *
     * @return OffloadEncoderInterface The encoder.
     */
    public function getEncoder()
    {
        if (!$this->encoder) {
            $this->encoder = new OffloadEncoderStandard();
        }
        return $this->encoder;
    }

    /**
     * Set the encoder.
     *
     * @param OffloadEncoderInterface $encoder The encoder to use.
     */
    public function setEncoder(OffloadEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * Get the decoder.
     *
     * @return OffloadEncoderInterface The decoder.
     */
    public function getDecoder()
    {
        if (!$this->decoder) {
            return $this->getEncoder();
        }
        return $this->decoder;
    }

    /**
     * Set the decoder.
     *
     * @param OffloadEncoderInterface $decoder The decoder to use.
     */
    public function setDecoder(OffloadEncoderInterface $decoder)
    {
        $this->decoder = $decoder;
    }

    /**
     * @inheritdoc
     */
    public function get($key, array $options = [])
    {
        $cached = $this->cache->get($this->namespace . $key, $options);
        $value = $this->decode($cached);
        if (is_array($value) && count($value) === 2) {
            list ($data, $exp) = $value;
            return new OffloadResult($data, true, $exp);
        }
        return OffloadResult::miss();
    }

    /**
     * @inheritdoc
     */
    public function getMany(array $keys, array $options = [])
    {
        $keys = $this->getKeysWithNamespace($keys);
        $cached = $this->cache->getMany($keys, $options);
        return array_map(function ($cached) {
            $value = $this->decode($cached);
            if (is_array($value) && count($value) === 2) {
                list ($data, $exp) = $value;
                return new OffloadResult($data, true, $exp);
            }
            return OffloadResult::miss();
        }, $cached);
    }

    /**
     * @inheritdoc
     */
    public function delete(array $keys, array $options = [])
    {
        $keys = $this->getKeysWithNamespace($keys);
        return $this->cache->delete($keys, $options);
    }

    /**
     * @inheritdoc
     */
    public function set($key, $data, $ttl_fresh_seconds, $ttl_stale_seconds, array $options = [])
    {
        $exp = time() + (int)$ttl_fresh_seconds;
        $ttl = $ttl_fresh_seconds + $ttl_stale_seconds;
        $encoded = $this->encode([$data, $exp]);
        return $this->cache->set($this->namespace . $key, $encoded, $ttl, $options);
    }

    /**
     * @return OffloadCacheInterface The underlying cache being used.
     */
    public function getBaseCache()
    {
        return $this->cache;
    }

    private function encode($data)
    {
        return $this->getEncoder()->encode($data);
    }

    private function decode($string)
    {
        if ($string === null) {
            return null;
        }
        return $this->getDecoder()->decode($string);
    }

    /**
     * Returns keys with namespace.
     *
     * @param array $keys The cache Keys.
     *
     * @return array List of keys with namespace.
     */
    private function getKeysWithNamespace(array $keys = [])
    {
        return array_map(function ($key) {
            return $this->namespace . $key;
        }, $keys);
    }
}
