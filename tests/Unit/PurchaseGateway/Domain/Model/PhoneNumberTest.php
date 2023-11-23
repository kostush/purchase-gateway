<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\PhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber;
use Tests\UnitTestCase;

class PhoneNumberTest extends UnitTestCase
{
    /**
     * @test
     * @return PhoneNumber
     * @throws Exception
     * @throws InvalidUserInfoPhoneNumber
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): PhoneNumber
    {
        $phoneNumber = PhoneNumber::create('514-000-0911');
        $this->assertInstanceOf(PhoneNumber::class, $phoneNumber);
        return $phoneNumber;
    }

    /**
     * @test
     * @param PhoneNumber $phoneNumber PhoneNumber
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_have_correct_data_when_created(PhoneNumber $phoneNumber): void
    {
        $this->assertEquals('5140000911', (string) $phoneNumber);
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoPhoneNumber
     * @throws Exception
     */
    public function it_should_throw_an_exception_when_incorrect_data_is_provided(): void
    {
        $this->expectException(InvalidUserInfoPhoneNumber::class);
        PhoneNumber::create('testPhoneNumber');
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoPhoneNumber
     * @throws Exception
     */
    public function it_should_return_digits_on_create(): void
    {
        $this->assertEquals("123", PhoneNumber::create('testPhoneNumber123'));
    }
}
