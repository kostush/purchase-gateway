<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Services\CrossSaleBillerFieldsFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\BillerNotSupportedException;
use Tests\UnitTestCase;

class CrossSaleBillerFieldsFactoryTest extends UnitTestCase
{
    /**
     * @test
     * @return BillerFields
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\BillerNotSupportedException
     */
    public function it_should_return_a_biller_fields_instance(): BillerFields
    {
        $billerFields = $this->createMock(RocketgateBillerFields::class);
        $billerFields->method('merchantId')->willReturn('123');
        $billerFields->method('merchantPassword')->willReturn('123');
        $billerFields->method('billerSiteId')->willReturn($this->faker->uuid);
        $billerFields->method('merchantCustomerId')->willReturn('123');

        $result = CrossSaleBillerFieldsFactory::create($billerFields, RocketgateBiller::BILLER_NAME);

        $this->assertInstanceOf(BillerFields::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_biller_fields_instance
     * @param BillerFields $billerFields The biller fields
     * @return void
     */
    public function the_returned_biller_fields_instance_should_be_correct(BillerFields $billerFields): void
    {
        $this->assertInstanceOf(RocketgateBillerFields::class, $billerFields);
    }

    /**
     * @test
     * @depends it_should_return_a_biller_fields_instance
     * @param RocketgateBillerFields $billerFields The biller fields
     * @return void
     */
    public function the_returned_rocketgate_biller_fields_instance_should_be_missing_the_invoice_id(RocketgateBillerFields $billerFields): void
    {
        $this->assertNull($billerFields->merchantInvoiceId());
    }

    /**
     * @test
     * @throws BillerNotSupportedException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    public function it_should_throw_exception_for_unknown_biller(): void
    {
        $this->expectException(BillerNotSupportedException::class);
        $result = CrossSaleBillerFieldsFactory::create(
            $this->createMock(RocketgateBillerFields::class),
            'Some biller name'
        );
    }
}
