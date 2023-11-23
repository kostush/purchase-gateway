<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Services\RocketgateBillerFieldsDataAdapter;
use Tests\UnitTestCase;

class RocketgateBillerFieldsDataAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_rocketgate_biller_fields_object(): void
    {
        $billerFields            = [
            'merchantId'         => '1234',
            'merchantPassword'   => $_ENV['ROCKETGATE_MERCHANT_PASSWORD_1'],
            'billerSiteId'       => '444ffb85-c826-4ed7-9f6f-33e11d3d2824',
            'sharedSecret'       => 'sharedSecret',
            'simplified3DS'      => true,
            'merchantCustomerId' => '1234567',
            'merchantInvoiceId'  => '321456'
        ];
        $billerFieldsDataAdapter = new RocketgateBillerFieldsDataAdapter();

        $billerFields = $billerFieldsDataAdapter->convert($billerFields);
        $this->assertInstanceOf(RocketgateBillerFields::class, $billerFields);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_if_invalid_data_provided(): void
    {
        $billerFields            = [
            'wrongIndex' => '1234'
        ];
        $billerFieldsDataAdapter = new RocketgateBillerFieldsDataAdapter();
        $this->expectException(\Exception::class);
        $billerFieldsDataAdapter->convert($billerFields);
    }
}
