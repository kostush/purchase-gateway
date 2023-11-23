<?php
declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\TaxType;
use Tests\UnitTestCase;

class TaxTypeTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function providerTypes():array
    {
        return [
            [TaxType::NO_TAX],
            ['sales'],
            [TaxType::UNKNOWN],
            ['vat']
        ];
    }

    /**
     * @test
     * @dataProvider providerTypes
     * @param  string $typeName Type Name.
     * @return void
     */
    public function it_should_return_tax_type_object(string $typeName) : void
    {
        $taxType = TaxType::create($typeName);
        $this->assertInstanceOf(TaxType::class, $taxType);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_unknown_type_when_it_doesnt_exist_tax_type() : void
    {
        $taxType = TaxType::create(null);
        $this->assertInstanceOf(TaxType::class, $taxType);
        $this->assertEquals($taxType->value(), TaxType::UNKNOWN);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_not_default_tax_type_for_different_type() : void
    {
        $taxTypeName = 'not-vat';
        $taxType     = TaxType::create($taxTypeName);
        $this->assertInstanceOf(TaxType::class, $taxType);
        $this->assertEquals($taxType->value(), $taxTypeName);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_no_tax_whitout_tax_information() : void
    {
        $noTaxType = TaxType::createFromTaxInformation(null);
        $this->assertEquals($noTaxType, TaxType::NO_TAX);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_lower_case_tax_type_name() : void
    {
        $taxType = TaxType::create('VAT');
        $this->assertEquals('vat', $taxType);
    }
}
