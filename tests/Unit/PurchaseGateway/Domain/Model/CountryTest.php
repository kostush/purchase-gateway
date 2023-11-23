<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use Tests\UnitTestCase;

class CountryTest extends UnitTestCase
{
    /**
     * @test
     * @return CountryCode
     * @throws Exception
     * @throws InvalidUserInfoCountry
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): CountryCode
    {
        $country = CountryCode::create('CA');
        $this->assertInstanceOf(CountryCode::class, $country);
        return $country;
    }

    /**
     * @test
     * @param CountryCode $country Country
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_have_correct_data_when_created(CountryCode $country): void
    {
        $this->assertEquals('CA', (string) $country);
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidUserInfoCountry
     */
    public function it_should_return_a_valid_uppercase_object_if_lowercase_data_is_provided(): void
    {
        $country = CountryCode::create('ca');
        $this->assertEquals('CA', (string) $country);
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoCountry
     * @throws Exception
     */
    public function it_should_throw_an_exception_when_incorrect_data_is_provided(): void
    {
        $this->expectException(InvalidUserInfoCountry::class);
        CountryCode::create('11');
    }
}
