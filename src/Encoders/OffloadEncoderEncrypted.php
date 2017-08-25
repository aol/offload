<?php

namespace Aol\Offload\Encoders;

use Aol\Offload\Exceptions\OffloadConfigurationException;
use Aol\Offload\Exceptions\OffloadEncoderException;

abstract class OffloadEncoderEncrypted implements OffloadEncoderInterface
{
    /** @var OffloadEncoderInterface The base encoder. */
    private $base;
    /** @var string A key ID present in the keys to use as the main key. */
    private $encryption_key_id;
    /** @var string[] An associative array of key ID to key. */
    private $keys;
    /** @var bool Whether to use the base encoder on a decode error. */
    private $use_base_on_decode_error;

    /**
     * Create a new encrypted encoder.
     *
     * @param OffloadEncoderInterface $base                     The encoder to encrypt.
     * @param string                  $encryption_key_id        A key ID present in the keys to use as the main key.
     * @param string[]                $keys                     An associative array of key ID to key.
     * @param bool                    $use_base_on_decode_error Whether to use the base encoder on a decode error.
     *
     * @throws OffloadConfigurationException If there was an issue with the options.
     */
    public function __construct(
        OffloadEncoderInterface $base,
        $encryption_key_id,
        array $keys,
        $use_base_on_decode_error = false
    ) {
        if (strpos($encryption_key_id, ':') !== false) {
            throw new OffloadConfigurationException('Invalid $encryption_key_id, cannot contain ":"');
        }
        if (empty($keys[$encryption_key_id])) {
            throw new OffloadConfigurationException('Missing $encryption_key_id entry in $keys');
        }
        $this->base = $base;
        $this->encryption_key_id = $encryption_key_id;
        $this->keys = $keys;
        $this->use_base_on_decode_error = $use_base_on_decode_error;
    }

    /**
     * @inheritdoc
     */
    public function encode($data)
    {
        try {
            $encoded = $this->base->encode($data);
            $key_id = $this->encryption_key_id;
            $encrypted = $this->encrypt($encoded, $this->keys[$key_id]);
            return $key_id . ':' . $encrypted;
        } catch (\Exception $ex) {
            throw new OffloadEncoderException('Offload could not serialize data: ' . $ex->getMessage(), 0, $ex);
        }
    }

    /**
     * @inheritdoc
     */
    public function decode($value)
    {
        $colon = strpos($value, ':');
        if (!$colon) {
            return $this->decodeFail(
                $value, 'Offload could not unserialize data, missing key ID.'
            );
        }
        $key_id = substr($value, 0, $colon);
        if (!isset($this->keys[$key_id])) {
            return $this->decodeFail(
                $value, "Offload could not unserialize data, unknown key ID ($key_id)."
            );
        }
        $encrypted = substr($value, $colon + 1);
        try {
            $encoded = $this->decrypt($encrypted, $this->keys[$key_id]);
        } catch (\Exception $ex) {
            return $this->decodeFail(
                $value, 'Offload could not unserialize data: ' . $ex->getMessage(), $ex
            );
        }
        return $this->base->decode($encoded);
    }

    private function decodeFail($encoded, $error_message, \Exception $previous = null)
    {
        if ($this->use_base_on_decode_error) {
            return $this->base->decode($encoded);
        }
        throw new OffloadEncoderException($error_message, 0, $previous);
    }

    /**
     * Encrypt the data using the given key.
     *
     * @param string $string The data to encrypt.
     * @param string $key    The key to use for encryption.
     *
     * @return string The encrypted value.
     */
    abstract protected function encrypt($string, $key);

    /**
     * Decrypt the data using the given key.
     *
     * @param string $string The encrypted value.
     * @param string $key    The key tor use for decryption.
     *
     * @return string The decrypted value.
     */
    abstract protected function decrypt($string, $key);
}
