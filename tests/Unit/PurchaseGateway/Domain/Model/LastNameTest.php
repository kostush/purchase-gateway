<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use Tests\UnitTestCase;

class LastNameTest extends UnitTestCase
{
    /**
     * @test
     * @return LastName
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoLastName
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): LastName
    {
        $lastName = LastName::create('Axe');
        $this->assertInstanceOf(LastName::class, $lastName);
        return $lastName;
    }

    /**
     * @test
     * @param LastName $lastName LastName
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_have_correct_data_when_created(LastName $lastName): void
    {
        $this->assertEquals('Axe', (string) $lastName);
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoLastName
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided_even_with_non_latin_characters(): void
    {
        $lastName = LastName::create('이름');

        $this->assertInstanceOf(LastName::class, $lastName);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoLastName
     */
    public function it_should_throw_an_exception_when_value_is_too_short(): void
    {
        $this->expectException(InvalidUserInfoLastName::class);
        LastName::create('a');
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoLastName
     */
    public function it_should_throw_an_exception_when_value_is_too_long(): void
    {
        $this->expectException(InvalidUserInfoLastName::class);
        LastName::create('fffffffffffffffffffff');
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoLastName
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_an_exception_when_value_has_whitespace(): void
    {
        $this->expectException(InvalidUserInfoLastName::class);
        LastName::create("te\tst");
    }

    /**
     * @test
     * @return LastName
     * @throws InvalidUserInfoLastName
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_valid_object_when_value_has_a_space(): LastName
    {
        $lastName = LastName::create('Mis ter');
        $this->assertInstanceOf(LastName::class, $lastName);
        return $lastName;
    }
}
