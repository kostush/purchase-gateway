<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrency;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrencySymbol;
use Tests\UnitTestCase;

class CurrencyCodeTest extends UnitTestCase
{
    /**
     * @test
     * @throws InvalidCurrency
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    public function it_should_throw_exception_when_invalid_currency_is_provided(): void
    {
        $this->expectException(InvalidCurrency::class);
        CurrencyCode::create('bcd');
    }

    /**
     * @test
     * @return CurrencyCode
     * @throws InvalidCurrency
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_valid_object_if_the_correct_currency_is_provided(): CurrencyCode
    {
        $currencyCode = CurrencyCode::create(CurrencyCode::CAD);
        $this->assertInstanceOf(CurrencyCode::class, $currencyCode);
        return $currencyCode;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_object_if_the_correct_currency_is_provided
     * @param CurrencyCode $currencyCode Currency Code
     * @throws \Exception
     * @return void
     */
    public function it_should_return_valid_symbol_for_currency_code_provided(CurrencyCode $currencyCode): void
    {
        $this->assertEquals('$', $currencyCode::symbolByCode((string) $currencyCode));
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_throw_exception_when_invalid_country_code_provided_to_get_currency_symbol(): void
    {
        $this->expectException(InvalidCurrencySymbol::class);
        CurrencyCode::symbolByCode('ABC');
    }
}
