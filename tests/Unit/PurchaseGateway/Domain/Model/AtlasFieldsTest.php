<?php
/**
 * Created by PhpStorm.
 * User: m_drugus
 * Date: 11/20/2019
 * Time: 9:42 PM
 */

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\AtlasFields;
use Tests\UnitTestCase;

class AtlasFieldsTest extends UnitTestCase
{
    /**
     * @test
     * @covers atlasCodeDecoded
     */
    public function it_should_return_array_with_invalid_atlas_code_if_decoding_fails()
    {
        $invalidAtlasCode = 'some code';

        $atlasFields = AtlasFields::create($invalidAtlasCode);

        $this->assertEquals([$invalidAtlasCode], $atlasFields->atlasCodeDecoded());
    }

    /**
     * @test
     * @covers atlasCodeDecoded
     */
    public function it_should_return_array_with_decoded_atlas_code_if_decoding_succeeds()
    {
        $validAtlasCode = 'eyJ1IjoiaW50ZXJuYWxwaCIsImNuIjoiMTAwMDczOTdfODgzODM3XzMxMzY1XzkxNDY1MiIsIm4iOjE0LCJzIjo5MCwiZSI6OTA2N30=';

        $atlasFields = AtlasFields::create($validAtlasCode);

        $this->assertEquals(
            json_decode(base64_decode($validAtlasCode), true),
            $atlasFields->atlasCodeDecoded()
        );
    }

    /**
     * @test
     * @covers atlasCodeDecoded
     */
    public function it_should_return_null_if_no_atlas_code_is_given()
    {
        $atlasFields = AtlasFields::create();

        $this->assertNull($atlasFields->atlasCodeDecoded());
    }
}
