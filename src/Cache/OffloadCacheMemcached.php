<?php

namespace Aol\Offload\Cache;

/**
 * Cache implementation for memcached.
 */
class OffloadCacheMemcached implements OffloadCacheInterface
{
	/** @var \Memcached The cache write connection. */
	private $write;
	/** @var \Memcached The cache read connection. */
	private $read;

	/**
	 * Create a new offload cache.
	 *
	 * @param \Memcached      $write The write connection.
	 * @param \Memcached|null $read  The read connection or null if same as write connection.
	 */
	public function __construct(\Memcached $write, \Memcached $read = null)
	{
		$this->write = $write;
		$this->read  = $read ?: $write;
	}

	/**
	 * @inheritdoc
	 */
	public function get($key, array $options = [])
	{
		$result = $this->read->get($key);
		$result = $this->read->getResultCode() !== \Memcached::RES_SUCCESS ? null : $result;
		$value  = $this->unserialize($result);
		return $value;
	}

	/**
	 * @inheritdoc
	 */
	public function getMany(array $keys, array $options = [])
	{
		$null   = null;
		$result = $this->read->getMulti($keys, $null, \Memcached::GET_PRESERVE_ORDER);
		$result = empty($result) ? null : array_values($result);
		$valid  = $result && $this->read->getResultCode() === \Memcached::RES_SUCCESS;
		$values = $valid ? array_map([$this, 'unserialize'], $result) : array_fill(0, count($keys), null);
		return $values;
	}

	/**
	 * @inheritdoc
	 */
	public function set($key, $value, $ttl_seconds, array $options = [])
	{
		$serialized = $this->serialize($value);
		$result     = $this->write->set($key, $serialized, (int)$ttl_seconds);
		return $result === true;
	}

	/**
	 * @inheritdoc
	 */
	public function delete(array $keys, array $options = [])
	{
		$result = $this->write->deleteMulti($keys);
		return $result ? count($keys) : 0;
	}

	protected function serialize($object)
	{
		return serialize($object);
	}

	protected function unserialize($string)
	{
		return ($string === null || $string === false) ? null : unserialize($string);
	}
}
