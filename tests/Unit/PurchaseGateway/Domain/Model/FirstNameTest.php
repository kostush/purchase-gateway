<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use Tests\UnitTestCase;

class FirstNameTest extends UnitTestCase
{
    /**
     * @test
     * @return FirstName
     * @throws InvalidUserInfoFirstName
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): FirstName
    {
        $firstName = FirstName::create('Mister');
        $this->assertInstanceOf(FirstName::class, $firstName);
        return $firstName;
    }

    /**
     * @test
     * @param FirstName $firstName FirstName
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_have_correct_data_when_created(FirstName $firstName): void
    {
        $this->assertEquals('Mister', (string) $firstName);
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoFirstName
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided_even_with_non_latin_characters(): void
    {
        $firstName = FirstName::create('이름');

        $this->assertInstanceOf(FirstName::class, $firstName);
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoFirstName
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_an_exception_when_value_is_too_short(): void
    {
        $this->expectException(InvalidUserInfoFirstName::class);
        FirstName::create('a');
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoFirstName
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_an_exception_when_value_is_too_long(): void
    {
        $this->expectException(InvalidUserInfoFirstName::class);
        FirstName::create('fffffffffffffffffffff');
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoFirstName
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_an_exception_when_value_has_whitespace(): void
    {
        $this->expectException(InvalidUserInfoFirstName::class);
        FirstName::create("te\tst");
    }

    /**
     * @test
     * @return FirstName
     * @throws InvalidUserInfoFirstName
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_valid_object_when_value_has_a_space(): FirstName
    {
        $firstName = FirstName::create('Mis ter');
        $this->assertInstanceOf(FirstName::class, $firstName);
        return $firstName;
    }
}
