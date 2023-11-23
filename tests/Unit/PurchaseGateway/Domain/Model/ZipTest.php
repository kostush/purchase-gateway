<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use Tests\UnitTestCase;

class ZipTest extends UnitTestCase
{
    /**
     * @test
     * @return Zip
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided()
    {
        $zip = Zip::create('800085');
        $this->assertInstanceOf(Zip::class, $zip);
        return $zip;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @param Zip $zip zip code
     * @return void
     */
    public function it_should_have_zip_code(Zip $zip)
    {
        $this->assertEquals('800085', (string) $zip);
    }

    /**
     * @test
     * @return void
     * @throws InvalidZipCodeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function if_should_throw_exception_if_invalid_data_is_provided()
    {
        $this->expectException(InvalidZipCodeException::class);
        Zip::create('1`');
    }

    /**
     * @test
     * @return void
     * @throws InvalidZipCodeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function if_should_throw_exception_if_a_code_longer_than_20_is_provided()
    {
        $this->expectException(InvalidZipCodeException::class);
        Zip::create('1234567890123456789012345677');
    }

    /**
     * @test
     * @return Zip
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     */
    public function it_should_return_a_valid_object_if_the_zip_code_has_space(): Zip
    {
        $zip = Zip::create('H4P 2H2');
        $this->assertInstanceOf(Zip::class, $zip);
        return $zip;
    }
}
