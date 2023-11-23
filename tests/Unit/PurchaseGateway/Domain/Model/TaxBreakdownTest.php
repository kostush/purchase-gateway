<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxBreakdown;
use Tests\UnitTestCase;

class TaxBreakdownTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException
     * @return TaxBreakdown
     */
    public function it_should_return_a_tax_break_down_object(): TaxBreakdown
    {
        $result = TaxBreakdown::create(
            Amount::create(1.11),
            Amount::create(2.11),
            Amount::create(3.11)
        );
        $this->assertInstanceOf(TaxBreakdown::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_tax_break_down_object
     * @param TaxBreakdown $taxBreakdown The tax breakdown object
     * @return \ProBillerNG\PurchaseGateway\Domain\Model\Amount
     */
    public function it_should_contain_a_before_taxes_amount(TaxBreakdown $taxBreakdown): Amount
    {
        $this->assertInstanceOf(Amount::class, $taxBreakdown->beforeTaxes());
        return $taxBreakdown->beforeTaxes();
    }

    /**
     * @test
     * @depends it_should_contain_a_before_taxes_amount
     * @param Amount $amount The amount object
     * @return void
     */
    public function it_should_contain_the_correct_value_for_before_taxes_amount(Amount $amount): void
    {
        $this->assertSame(1.11, $amount->value());
    }

    /**
     * @test
     * @depends it_should_return_a_tax_break_down_object
     * @param TaxBreakdown $taxBreakdown The tax breakdown object
     * @return Amount
     */
    public function it_should_contain_a_taxes_amount(TaxBreakdown $taxBreakdown): Amount
    {
        $this->assertInstanceOf(Amount::class, $taxBreakdown->taxes());
        return $taxBreakdown->taxes();
    }

    /**
     * @test
     * @depends it_should_contain_a_taxes_amount
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\Amount $amount The amount object
     * @return void
     */
    public function it_should_contain_the_correct_value_for_taxes_amount(Amount $amount): void
    {
        $this->assertSame(2.11, $amount->value());
    }

    /**
     * @test
     * @depends it_should_return_a_tax_break_down_object
     * @param TaxBreakdown $taxBreakdown The tax breakdown object
     * @return Amount
     */
    public function it_should_contain_a_after_taxes_amount(TaxBreakdown $taxBreakdown): Amount
    {
        $this->assertInstanceOf(Amount::class, $taxBreakdown->beforeTaxes());
        return $taxBreakdown->afterTaxes();
    }

    /**
     * @test
     * @depends it_should_contain_a_after_taxes_amount
     * @param Amount $amount The amount object
     * @return void
     */
    public function it_should_contain_the_correct_value_for_after_taxes_amount(Amount $amount): void
    {
        $this->assertSame(3.11, $amount->value());
    }

    /**
     * @test
     * @depends it_should_return_a_tax_break_down_object
     * @param TaxBreakdown $taxBreakdown The tax breakdown object
     * @return void
     */
    public function to_array_should_return_an_array_with_all_the_class_property_values(
        TaxBreakdown $taxBreakdown
    ): void {
        $array = $taxBreakdown->toArray();

        $this->assertArrayHasKey('beforeTaxes', $array);
        $this->assertArrayHasKey('taxes', $array);
        $this->assertArrayHasKey('afterTaxes', $array);
    }
}
