<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use Tests\UnitTestCase;

class EmailTest extends UnitTestCase
{
    /**
     * @test
     * @return Email
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoEmail
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): Email
    {
        $email = Email::create('test-purchase@test.mindgeek.com');
        $this->assertInstanceOf(Email::class, $email);
        return $email;
    }

    /**
     * @test
     * @param Email $email Email
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_have_correct_data_when_created(Email $email): void
    {
        $this->assertEquals('test-purchase@test.mindgeek.com', (string) $email);
    }

    /**
     * @test
     * @param Email $email Email
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_the_correct_email_domain(Email $email): void
    {
        $this->assertEquals('test.mindgeek.com', (string) $email->domain());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoEmail
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided_even_with_special_characters(): void
    {
        $email = Email::create('이메일@test.mindgeek.com');

        $this->assertInstanceOf(Email::class, $email);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoEmail
     */
    public function it_should_throw_an_exception_when_incorrect_data_is_provided(): void
    {
        $this->expectException(InvalidUserInfoEmail::class);
        Email::create('11');
    }
}
