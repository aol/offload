<?php

namespace Aol\Offload\Encoders;

interface OffloadEncoderInterface
{
    /**
     * Encode an object to a serialized string.
     *
     * @param mixed $object The object to encode.
     *
     * @return string The serialized string.
     */
    public function encode($object);

    /**
     * Decode a string back into an object.
     *
     * @param string $string The serialized string.
     *
     * @return mixed A reconstituted object or null.
     */
    public function decode($string);
}
