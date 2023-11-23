<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use Tests\UnitTestCase;

class UsernameTest extends UnitTestCase
{
    /**
     * @test
     * @return Username
     * @throws Exception
     * @throws InvalidUserInfoUsername
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): Username
    {
        $username = Username::create('test1234');
        $this->assertInstanceOf(Username::class, $username);
        return $username;
    }

    /**
     * @test
     * @return Username
     * @throws Exception
     * @throws InvalidUserInfoUsername
     */
    public function it_should_return_a_valid_object_if_a_string_of_more_than_16_digits_is_provided(): Username
    {
        $username = Username::create('12345678912345678');
        $this->assertInstanceOf(Username::class, $username);
        return $username;
    }

    /**
     * @test
     * @param Username $username Username
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_have_correct_data_when_created(Username $username): void
    {
        $this->assertEquals('test1234', (string) $username);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws InvalidUserInfoUsername
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided_even_with_non_latin_characters(): void
    {
        $username = Username::create('사용자이름');

        $this->assertInstanceOf(Username::class, $username);
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoUsername
     * @throws Exception
     */
    public function it_should_accept_underscore_on_the_username(): void
    {
        $username = Username::create('test_1234');
        $this->assertEquals('test_1234', (string) $username);
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoUsername
     * @throws Exception
     */
    public function it_should_throw_an_exception_when_it_contains_cc_number_as_username(): void
    {
        $this->expectException(InvalidUserInfoUsername::class);
        Username::create($this->faker->creditCardNumber('Visa'));
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoUsername
     * @throws Exception
     */
    public function it_should_throw_an_exception_when_it_is_empty(): void
    {
        $this->expectException(InvalidUserInfoUsername::class);
        Username::create('');
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoUsername
     * @throws Exception
     */
    public function it_should_throw_an_exception_when_it_has_more_than_max_length(): void
    {
        $this->expectException(InvalidUserInfoUsername::class);
        Username::create(str_repeat("abcde12345_", Username::MAX_USERNAME_LENGTH / 10));
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoUsername
     * @throws Exception
     */
    public function it_should_accept_username_with_min_length(): void
    {
        $username = Username::create(str_repeat("a", Username::MIN_USERNAME_LENGTH));
        $this->assertEquals('a', (string) $username);
    }
}
