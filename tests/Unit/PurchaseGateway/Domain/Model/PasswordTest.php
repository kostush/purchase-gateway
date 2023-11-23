<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword;
use Tests\UnitTestCase;

class PasswordTest extends UnitTestCase
{
    /**
     * @test
     * @return Password
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoPassword
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): Password
    {
        $password = Password::create('test12345');
        $this->assertInstanceOf(Password::class, $password);
        return $password;
    }

    /**
     * @test
     * @param Password $password Password
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_have_correct_data_when_created(Password $password): void
    {
        $this->assertEquals('test12345', (string) $password);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoPassword
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided_even_with_non_latin_characters(): void
    {
        $password = Password::create('암호');

        $this->assertInstanceOf(Password::class, $password);
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoPassword
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_an_exception_when_incorrect_data_is_provided(): void
    {
        $this->expectException(InvalidUserInfoPassword::class);
        Password::create('1111111111111111111111111111111111111111111111111111111111111');
    }
}
