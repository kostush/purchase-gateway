<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxType;
use Tests\UnitTestCase;

class TaxInformationTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException
     * @return TaxInformation
     */
    public function it_should_return_a_tax_information_object(): TaxInformation
    {
        $result = TaxInformation::create(
            'HST',
            Amount::create(1.19),
            'taxApplicationId',
            'customData',
            TaxType::create('sales')
        );

        $this->assertInstanceOf(TaxInformation::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_tax_information_object
     * @param TaxInformation $taxInformation The tax information object
     * @return void
     */
    public function it_should_contain_correct_tax_name(TaxInformation $taxInformation): void
    {
        $this->assertSame('HST', $taxInformation->taxName());
    }

    /**
     * @test
     * @depends it_should_return_a_tax_information_object
     * @param TaxInformation $taxInformation The tax information object
     * @return TaxInformation
     */
    public function it_should_contain_a_tax_rate_object(TaxInformation $taxInformation): TaxInformation
    {
        $this->assertInstanceOf(Amount::class, $taxInformation->taxRate());
        return $taxInformation;
    }

    /**
     * @test
     * @depends it_should_return_a_tax_information_object
     * @param TaxInformation $taxInformation The tax information object
     * @return void
     */
    public function it_should_contain_correct_tax_application_id(TaxInformation $taxInformation): void
    {
        $this->assertEquals('taxApplicationId', $taxInformation->taxApplicationId());
    }

    /**
     * @test
     * @depends it_should_return_a_tax_information_object
     * @param TaxInformation $taxInformation The tax information object
     * @return void
     */
    public function it_should_contain_correct_custom_data(TaxInformation $taxInformation): void
    {
        $this->assertEquals('customData', $taxInformation->taxCustom());
    }

    /**
     * @test
     * @depends it_should_contain_a_tax_rate_object
     * @param TaxInformation $taxInformation The tax information object
     * @return void
     */
    public function it_should_contain_the_correct_tax_rate_amount(TaxInformation $taxInformation): void
    {
        $this->assertSame(1.19, $taxInformation->taxRate()->value());
    }

    /**
     * @test
     * @depends it_should_contain_a_tax_rate_object
     * @param TaxInformation $taxInformation The tax information object
     * @return void
     */
    public function it_should_contain_the_correct_tax_type(TaxInformation $taxInformation): void
    {
        $this->assertSame('sales', (string) $taxInformation->taxType());
    }

    /**
     * @test
     * @depends it_should_return_a_tax_information_object
     * @param TaxInformation $taxInformation The tax information object
     * @return void
     */
    public function to_array_should_return_an_array_with_all_the_class_property_values(
        TaxInformation $taxInformation
    ): void {
        $array = $taxInformation->toArray();

        $this->assertArrayHasKey('taxRate', $array);
        $this->assertArrayHasKey('taxName', $array);
        $this->assertArrayHasKey('taxApplicationId', $array);
        $this->assertArrayHasKey('custom', $array);
        $this->assertArrayHasKey('taxType', $array);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    public function to_array_should_not_return_tax_name_if_null(): void
    {
        $taxInformation = TaxInformation::create(
            null,
            Amount::create(1.19),
            'taxApplicationId',
            'customData',
            TaxType::create('vat')
        );

        $this->assertArrayNotHasKey('taxName', $taxInformation->toArray());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    public function to_array_should_not_return_tax_application_id_if_null(): void
    {
        $taxInformation = TaxInformation::create(
            'HST',
            Amount::create(1.19),
            null,
            'customData',
            TaxType::create('sales')
        );

        $this->assertArrayNotHasKey('taxApplicationId', $taxInformation->toArray());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    public function to_array_should_not_return_tax_custom_if_null(): void
    {
        $taxInformation = TaxInformation::create(
            'HST',
            Amount::create(1.19),
            'taxApplicationId',
            null,
            TaxType::create('sales')
        );

        $this->assertArrayNotHasKey('custom', $taxInformation->toArray());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    public function it_should_return_unknown_for_no_tax_type(): void
    {
        $taxInformation = TaxInformation::create(
            'HST',
            Amount::create(1.19),
            'taxApplicationId',
            null,
            TaxType::create(null)
        );

        $this->assertEquals(TaxType::UNKNOWN, $taxInformation->taxType());
    }
}
