<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFieldsFactoryService;
use Tests\UnitTestCase;

class BillerFieldsFactoryServiceTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_biller_fields_object(): void
    {
        $billerFields = [
            'accountId' => '1234',
            'siteTag'   => 'testTag'
        ];
        $result       = BillerFieldsFactoryService::create(new NetbillingBiller(), $billerFields);

        $this->assertInstanceOf(BillerFields::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_an_exception_if_invalid_data_provided(): void
    {
        $this->expectException(\Exception::class);

        $billerFields = [
            'wrongIndex' => '1234',
            'siteTag'    => 'testTag'
        ];

        BillerFieldsFactoryService::create(new NetbillingBiller(), $billerFields);
    }
}
