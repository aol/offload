<?php

namespace Aol\Offload\Encoders;

class OffloadEncoderTest extends \PHPUnit_Framework_TestCase
{
    /** @var OffloadEncoderInterface */
    protected $encoder;

    public function setUp()
    {
        $this->encoder = new OffloadEncoderStandard();
    }

    /**
     * @dataProvider dataWorks
     */
    public function testWorks($data)
    {
        $encoded = $this->encoder->encode($data);
        self::assertTrue(is_string($encoded));
        self::assertNotEquals($data, $encoded);
        $decoded = $this->encoder->decode($encoded);
        self::assertEquals($data, $decoded);
    }

    public function dataWorks()
    {
        return [
            ['string'],
            [123],
            [123.22223],
            [true],
            [false],
            [[1,2,3]],
            [['1',2,false,true]],
            [['assoc'=>1,'two'=>true,'three'=>[1,2,true]]],
            [new \stdClass()],
            [call_user_func(function () {
                $obj = new \stdClass();
                $obj->x = ['hello',1,2,3];
                return $obj;
            })]
        ];
    }
}
