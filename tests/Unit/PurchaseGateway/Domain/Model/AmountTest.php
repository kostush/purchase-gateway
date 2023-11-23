<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use Tests\UnitTestCase;

class AmountTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException
     * @return Amount
     */
    public function it_should_return_an_amount_object(): Amount
    {
        $result = Amount::create(4.44);
        $this->assertInstanceOf(Amount::class, $result);
        return $result;
    }

    /**
     * @test
     * @param Amount $amount The amount object
     * @depends it_should_return_an_amount_object
     * @return void
     */
    public function it_should_have_the_correct_value(Amount $amount): void
    {
        $this->assertSame(4.44, $amount->value());
    }

    /**
     * @test
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    public function it_should_throw_invalid_amount_exception_for_invalid_value(): void
    {
        $this->expectException(InvalidAmountException::class);
        Amount::create(-0.22);
    }

    /**
     * @test
     * @throws \TypeError
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    public function it_should_throw_type_exception_for_invalid_argument(): void
    {
        $this->expectException(\TypeError::class);
        Amount::create('string');
    }
}
