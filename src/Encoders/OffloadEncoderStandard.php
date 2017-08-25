<?php

namespace Aol\Offload\Encoders;

class OffloadEncoderStandard implements OffloadEncoderInterface
{
    /**
     * @inheritdoc
     */
    public function encode($object)
    {
        return serialize($object);
    }

    /**
     * @inheritdoc
     */
    public function decode($string)
    {
        return ($string === null || $string === false) ? null : unserialize($string);
    }
}
