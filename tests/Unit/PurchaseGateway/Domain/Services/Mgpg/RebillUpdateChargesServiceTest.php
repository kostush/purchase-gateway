<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services\Mgpg;

use ProbillerMGPG\SubsequentOperations\Init\Item\Price\PriceInfoWithTax;
use ProbillerMGPG\SubsequentOperations\Init\Item\RecurringChargeItem;
use ProbillerMGPG\SubsequentOperations\Init\Item\RecurringChargeItemWithTax;
use ProbillerMGPG\SubsequentOperations\Init\Operation\SubscriptionTrialUpgrade;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\RebillFieldRequiredException;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\RebillUpdateChargesService;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\RetrieveTransactionIdService;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\InitRebillUpdateRequest;
use Tests\UnitTestCase;

class RebillUpdateChargesServiceTest extends UnitTestCase
{
    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_recurring_charge_item_with_rebill_fields_and_no_tax(): void
    {
        //GIVEN
        $request                      = $this->createMock(InitRebillUpdateRequest::class);
        $retrieveTransactionIdService = $this->createMock(RetrieveTransactionIdService::class);

        $initialDays   = 1;
        $rebillDays    = 2;
        $initialAmount = 3.0;
        $rebillAmount  = 4.0;

        $site = $this->createMock(Site::class);
        $site->method('siteId')->willReturn(SiteId::create());
        $site->method('name')->willReturn('test');

        $request->expects($this->any())->method('getItemId')->willReturn($this->faker->uuid);
        $request->expects($this->any())->method('getBundleId')->willReturn($this->faker->uuid);
        $request->expects($this->any())->method('getSite')->willReturn($site);

        //When
        $request->expects($this->any())->method('getBusinessTransactionOperation')->willReturn('subscriptionTrialUpgrade');
        $retrieveTransactionIdService->method('findByItemIdOrReturnItemId')->willReturn($this->faker->uuid);
        //--- and with rebill
        $request->expects($this->any())->method('getRebillDays')->willReturn($rebillDays);
        $request->expects($this->any())->method('getRebillAmount')->willReturn($rebillAmount);
        $request->expects($this->any())->method('getInitialDays')->willReturn($initialDays);
        $request->expects($this->any())->method('getAmount')->willReturn($initialAmount);
        //--- and no tax
        $request->expects($this->any())->method('getTax')->willReturn([]);
        $request->expects($this->any())->method('getTaxInitialAmount')->willReturn([]);


        $service = new RebillUpdateChargesService($retrieveTransactionIdService);

        $result = $service->create($request);
        //THEN
        $this->assertInstanceOf(SubscriptionTrialUpgrade::class, $result[0]);
        $this->assertInstanceOf(RecurringChargeItem::class, ($result[0])->items[0]);

        /** @var RecurringChargeItem $charge */
        $charge = ($result[0])->items[0];
        $this->assertEquals($initialDays, $charge->toArray()['priceInfo']['expiresInDays']);
        $this->assertEquals($rebillDays, $charge->toArray()['rebill']['rebillDays']);
        $this->assertEquals($initialAmount, $charge->toArray()['priceInfo']['finalPrice']);
        $this->assertEquals($rebillAmount, $charge->toArray()['rebill']['finalPrice']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_recurring_charge_with_tax_when_rebill_fields_are_with_tax(): void
    {
        //GIVEN
        $request                      = $this->createMock(InitRebillUpdateRequest::class);
        $retrieveTransactionIdService = $this->createMock(RetrieveTransactionIdService::class);

        $initialDays   = 1;
        $rebillDays    = 2;
        $initialAmount = 3.0;
        $rebillAmount  = 4.0;

        $taxInitialAmount = [
            "beforeTaxes" => 10.0,
            "taxes"       => 26.8,
            "afterTaxes"  => 12.0
        ];

        $taxRebillAmount = [
            "beforeTaxes" => 11.0,
            "taxes"       => 27.8,
            "afterTaxes"  => 13.0
        ];

        $tax = [
            "taxApplicationId" => "60bf5bcb-ac64-496c-acc5-9c7cf54a1869",
            "taxName"          => "VAT",
            "taxRate"          => 0.05,
            "taxType"          => "vat",
            'initialAmount' => $taxInitialAmount
        ];

        $site = $this->createMock(Site::class);
        $site->method('siteId')->willReturn(SiteId::create());
        $site->method('name')->willReturn('test');

        $request->expects($this->any())->method('getItemId')->willReturn($this->faker->uuid);
        $request->expects($this->any())->method('getBundleId')->willReturn($this->faker->uuid);
        $request->expects($this->any())->method('getSite')->willReturn($site);

        //When
        $request->expects($this->any())->method('getBusinessTransactionOperation')->willReturn('subscriptionTrialUpgrade');
        $retrieveTransactionIdService->method('findByItemIdOrReturnItemId')->willReturn($this->faker->uuid);
        //--- and with rebill
        $request->expects($this->any())->method('getRebillDays')->willReturn($rebillDays);
        $request->expects($this->any())->method('getRebillAmount')->willReturn($rebillAmount);
        $request->expects($this->any())->method('getInitialDays')->willReturn($initialDays);
        $request->expects($this->any())->method('getAmount')->willReturn($initialAmount);
        //--- and with tax
        $request->expects($this->any())->method('getTax')->willReturn($tax);
        $request->expects($this->any())->method('getTaxInitialAmount')->willReturn($taxInitialAmount);
        $request->expects($this->any())->method('getTaxRebillAmount')->willReturn($taxRebillAmount);
        $request->expects($this->any())->method('getInitialAmountBeforeTaxes')->willReturn($taxInitialAmount["beforeTaxes"]);
        $request->expects($this->any())->method('getInitialAmountTaxes')->willReturn($taxInitialAmount["taxes"]);
        $request->expects($this->any())->method('getInitialAmountAfterTaxes')->willReturn($taxInitialAmount["afterTaxes"]);
        $request->expects($this->any())->method('getRebillAmountBeforeTaxes')->willReturn($taxRebillAmount["beforeTaxes"]);
        $request->expects($this->any())->method('getRebillAmountTaxes')->willReturn($taxRebillAmount["taxes"]);
        $request->expects($this->any())->method('getRebillAmountAfterTaxes')->willReturn($taxRebillAmount["afterTaxes"]);


        $service = new RebillUpdateChargesService($retrieveTransactionIdService);

        $result = $service->create($request);
        //THEN
        $this->assertInstanceOf(SubscriptionTrialUpgrade::class, $result[0]);
        $this->assertInstanceOf(RecurringChargeItemWithTax::class, ($result[0])->items[0]);
        /** @var RecurringChargeItem $charge */
        $charge = ($result[0])->items[0];
        $this->assertEquals($initialDays, $charge->toArray()['priceInfo']['expiresInDays']);
        $this->assertEquals($rebillDays, $charge->toArray()['rebill']['rebillDays']);
        $this->assertEquals($taxInitialAmount["afterTaxes"], $charge->toArray()['priceInfo']['finalPrice']);
        $this->assertEquals($taxRebillAmount["afterTaxes"], $charge->toArray()['rebill']['finalPrice']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_exception_without_rebill(): void
    {
        //GIVEN
        $request                      = $this->createMock(InitRebillUpdateRequest::class);
        $retrieveTransactionIdService = $this->createMock(RetrieveTransactionIdService::class);

        $site = $this->createMock(Site::class);
        $site->method('siteId')->willReturn(SiteId::create());
        $site->method('name')->willReturn('test');

        $request->expects($this->any())->method('getItemId')->willReturn($this->faker->uuid);
        $request->expects($this->any())->method('getBundleId')->willReturn($this->faker->uuid);
        $request->expects($this->any())->method('getSite')->willReturn($site);
        $request->expects($this->any())->method('getBusinessTransactionOperation')->willReturn('subscriptionTrialUpgrade');
        $retrieveTransactionIdService->method('findByItemIdOrReturnItemId')->willReturn($this->faker->uuid);

        //When
        $request->expects($this->any())->method('getRebillDays')->willReturn(null);
        $request->expects($this->any())->method('getRebillAmount')->willReturn(null);


        $service = new RebillUpdateChargesService($retrieveTransactionIdService);

        //THEN
        $this->expectException(RebillFIeldRequiredException::class);
        $service->create($request);
    }
}
