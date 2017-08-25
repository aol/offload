<?php

namespace Aol\Offload\Encoders;

class CacheLinkEncoderEncryptedTest extends OffloadEncoderTest
{
    public function setUp()
    {
        parent::setUp();
        $this->encoder = new OffloadEncoderEncryptedAes256($this->encoder, 'foo', [
            'foo' => 'fookey',
            'bar' => 'barkey'
        ]);
    }

    /**
     * @expectedException \Aol\Offload\Exceptions\OffloadConfigurationException
     * @expectedExceptionMessage Invalid $encryption_key_id
     */
    public function testEncryptionIdCannotHaveColon()
    {
        new OffloadEncoderEncryptedAes256(new OffloadEncoderStandard(), 'foo:bar', []);
    }

    /**
     * @expectedException \Aol\Offload\Exceptions\OffloadConfigurationException
     * @expectedExceptionMessage Missing $encryption_key_id
     */
    public function testEncryptionIdMustBePresentInKeys()
    {
        new OffloadEncoderEncryptedAes256(new OffloadEncoderStandard(), 'foo', ['bar'=>'baz']);
    }

    /**
     * @expectedException \Aol\Offload\Exceptions\OffloadEncoderException
     * @expectedExceptionMessage missing key ID
     */
    public function testDecodeMustReceiveKeyWithColon()
    {
        $this->encoder->decode('not_a_valid_key');
    }

    /**
     * @expectedException \Aol\Offload\Exceptions\OffloadEncoderException
     * @expectedExceptionMessage unknown key ID
     */
    public function testDecodeMustReceiveValidKey()
    {
        $this->encoder->decode('bad_key:foobar');
    }

    /**
     * @expectedException \Aol\Offload\Exceptions\OffloadEncoderException
     * @expectedExceptionMessage could not unserialize data
     */
    public function testDecodeFailsWithBadData()
    {
        $this->encoder->decode('foo:' . base64_encode('$%^&*('));
    }

    public function testDecodeFailureUsesBaseWhenFlagSet()
    {
        $base = self::getMockBuilder('Aol\Offload\Encoders\OffloadEncoderStandard')
            ->setMethods(['decode'])->getMock();
        $base->expects(self::once())->method('decode')->willReturn('test');
        $encoder = new OffloadEncoderEncryptedAes256($base, 'foo', ['foo' => 'bar'], true);
        self::assertEquals('test', $encoder->decode('foo:' . base64_encode('$%^&*(')));
    }

    public function testDecodesStandardIfNotEncrypted()
    {
        $base = new OffloadEncoderStandard();
        $encoder = new OffloadEncoderEncryptedAes256($base, 'foo', ['foo' => 'bar'], true);
        $encoded = $base->encode('hello world');
        self::assertEquals('hello world', $encoder->decode($encoded));
    }

    /**
     * @expectedException \Aol\Offload\Exceptions\OffloadEncoderException
     * @expectedExceptionMessage could not serialize data
     */
    public function testEncodeRethrowsException()
    {
        $base = self::getMockBuilder('Aol\Offload\Encoders\OffloadEncoderStandard')
            ->setMethods(['encode'])->getMock();
        $base->expects(self::once())->method('encode')->willThrowException(new \RuntimeException('oh noes'));
        $encoder = new OffloadEncoderEncryptedAes256($base, 'foo', ['foo' => 'bar'], true);
        $encoder->encode('testing');
    }
}
