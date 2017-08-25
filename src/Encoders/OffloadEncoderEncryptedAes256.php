<?php

namespace Aol\Offload\Encoders;

class OffloadEncoderEncryptedAes256 extends OffloadEncoderEncrypted
{
    const ENCRYPTION_METHOD = 'AES-256-CBC';

    protected function encrypt($string, $key)
    {
        $iv = random_bytes(16);
        return $iv . openssl_encrypt($string, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    }

    protected function decrypt($string, $key)
    {
        $iv = mb_substr($string, 0, 16, '8bit');
        $encrypted = mb_substr($string, 16, null, '8bit');
        return openssl_decrypt($encrypted, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    }
}
