<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\NetbillingBinRoutingAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\NetbillingBinRoutingTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeErrorException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use Tests\UnitTestCase;

class BinRoutingNetbillingTranslatingServiceTest extends UnitTestCase
{
    /**
     * @var NetbillingBinRoutingTranslatingService
     */
    private $netbillingBinRoutingTranslatingService;

    /**
     * string
     */
    const UUID = 'db577af6-b2ae-11e9-a2a3-2a2ae2dbcce4';

    /**
     * @var NetbillingBinRoutingAdapter|MockObject
     */
    private $netbillingBinRoutingAdapterMock;

    /**
     * @var BusinessGroupId
     */
    private $businessGroupId;

    /**
     * string
     */
    const UUID_2 = 'f291f110-d1a0-331d-a245-bc71fb168ce0';

    /**
     * @var BillerMapping
     */
    private $netbillingBillerMapping;

    /**
     * @var MockObject|Site
     */
    private $site;

    /**
     * Setup method
     * @return void
     * @throws ValidationException
     * @throws Exception
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->site = $this->createMock(Site::class);
        $this->site->method('siteId')->willReturn(
            SiteId::createFromString(self::UUID)
        );
        $this->site->method('isBinRoutingServiceEnabled')->willReturn(true);

        $this->businessGroupId = BusinessGroupId::createFromString(self::UUID_2);

        $this->netbillingBinRoutingAdapterMock = $this->createMock(NetbillingBinRoutingAdapter::class);
        $this->netbillingBinRoutingAdapterMock->method('retrieve')->willReturn(new BinRoutingCollection());

        $this->netbillingBillerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            $this->businessGroupId,
            'USD',
            'netbilling',
            NetbillingBillerFields::create(
                $_ENV['NETBILLING_ACCOUNT_ID'],
                $_ENV['NETBILLING_SITE_TAG'],
                null,
                $_ENV['NETBILLING_MERCHANT_PASSWORD']
            )
        );

        $this->purchaseProcessMockWithNetbillingSettings();
    }

    /**
     * @return MockObject|PurchaseProcess
     * @throws ValidationException
     * @throws Exception
     */
    private function purchaseProcessMockWithNetbillingSettings()
    {
        $purchaseProcessMock = $this->createMock(PurchaseProcess::class);
        $fraudAdvice         = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('bin')->willReturn(Bin::createFromCCNumber($this->faker->creditCardNumber));
        $purchaseProcessMock->method('fraudAdvice')->willReturn($fraudAdvice);

        $purchaseProcessMock->method('sessionId')->willReturn(SessionId::create());
        $purchaseProcessMock->method('fraudAdvice')->willReturn($fraudAdvice);

        $purchaseProcessMock->method('sessionId')->willReturn(SessionId::create());

        return $purchaseProcessMock;
    }

    /**
     * @test
     * @return void
     * @throws BinRoutingCodeApiException
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws Exception
     * @throws ValidationException
     */
    public function it_should_return_a_bin_routing_collection_with_netbilling(): void
    {
        $binCollection = new BinRoutingCollection();
        $binCollection->offsetSet(
            self::UUID . '_' . 1,
            BinRouting::create(1, "123", 'BankName1')
        );
        $binCollection->offsetSet(
            self::UUID . '_' . 2,
            BinRouting::create(2, "456", 'BankName2')
        );

        $netbillingBinRoutingAdapterMock = $this->createMock(NetbillingBinRoutingAdapter::class);

        $netbillingBinRoutingAdapterMock->method('retrieve')->willReturn($binCollection);

        $this->netbillingBinRoutingTranslatingService = new NetbillingBinRoutingTranslatingService($netbillingBinRoutingAdapterMock);

        $purchaseProcessMock = $this->purchaseProcessMockWithNetbillingSettings();
        $purchaseProcessMock->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new NetbillingBiller()]))
        );
        $purchaseProcessMock->method('gatewaySubmitNumber')->willReturn(0);

        $result = $this->netbillingBinRoutingTranslatingService->retrieveRoutingCodes(
            $purchaseProcessMock,
            ItemId::createFromString(self::UUID),
            $this->site,
            $this->netbillingBillerMapping
        );

        $this->assertInstanceOf(BinRoutingCollection::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws BinRoutingCodeApiException
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws Exception
     * @throws ValidationException
     */
    public function it_should_use_bin_routing_from_payment_template_for_netbilling_sec_rev(): void
    {
        $netbillingBinRoutingAdapterMock = $this->createMock(NetbillingBinRoutingAdapter::class);

        $this->netbillingBinRoutingTranslatingService = new NetbillingBinRoutingTranslatingService($netbillingBinRoutingAdapterMock);

        $paymentTemplate = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('billerFields')->willReturn(
            [
                'originId'   => '113844288421',
                'binRouting' => 'INT\/PX#100XTxEP'
            ]
        );

        $paymentTemplateCollection = new PaymentTemplateCollection();
        $paymentTemplateCollection->add($paymentTemplate);
        $purchaseProcessMock = $this->purchaseProcessMockWithNetbillingSettings();
        $purchaseProcessMock->method('paymentTemplateCollection')->willReturn($paymentTemplateCollection);
        $purchaseProcessMock->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new NetbillingBiller()]))
        );
        $purchaseProcessMock->method('gatewaySubmitNumber')->willReturn(0);

        $purchaseProcessMock->method('retrieveSelectedPaymentTemplate')
            ->willReturn($paymentTemplate);

        /** @var BinRouting $binRouting */
        $binRouting = $this->netbillingBinRoutingTranslatingService->retrieveRoutingCodes(
            $purchaseProcessMock,
            ItemId::createFromString(self::UUID),
            $this->site,
            $this->netbillingBillerMapping
        )->first();

        $this->assertEquals($binRouting->routingCode(), $paymentTemplate->billerFields()['binRouting']);
    }

    /**
     * @test
     * @return void
     * @throws BinRoutingCodeApiException
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws ValidationException
     * @throws \Exception
     */
    public function it_should_send_join_submit_number_one_on_first_attempt_with_netbilling()
    {
        $this->netbillingBinRoutingAdapterMock->expects($this->once())->method('retrieve')->with(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            1,
            $this->anything(),
            (string) $this->businessGroupId->value()
        );

        $this->netbillingBinRoutingTranslatingService = new NetbillingBinRoutingTranslatingService($this->netbillingBinRoutingAdapterMock);

        $purchaseProcessMock = $this->purchaseProcessMockWithNetbillingSettings();
        $purchaseProcessMock->method('gatewaySubmitNumber')->willReturn(0);
        $purchaseProcessMock->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection([new NetbillingBiller()]),
                new NetbillingBiller(),
                1
            )
        );

        $this->netbillingBinRoutingTranslatingService->retrieveRoutingCodes(
            $purchaseProcessMock,
            ItemId::createFromString(self::UUID),
            $this->site,
            $this->netbillingBillerMapping
        );
    }

    /**
     * @test
     * @return void
     * @throws BinRoutingCodeApiException
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws ValidationException
     * @throws \Exception
     */
    public function it_should_send_join_submit_number_two_on_second_attempt_with_netbilling()
    {
        $this->netbillingBinRoutingAdapterMock->expects($this->once())->method('retrieve')->with(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            2,
            $this->anything(),
            (string) $this->businessGroupId->value()
        );

        $this->netbillingBinRoutingTranslatingService = new NetbillingBinRoutingTranslatingService($this->netbillingBinRoutingAdapterMock);

        $purchaseProcessMock = $this->purchaseProcessMockWithNetbillingSettings();
        $purchaseProcessMock->method('gatewaySubmitNumber')->willReturn(1);
        $purchaseProcessMock->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection([new NetbillingBiller()]),
                new NetbillingBiller(),
                2
            )
        );

        $this->netbillingBinRoutingTranslatingService->retrieveRoutingCodes(
            $purchaseProcessMock,
            ItemId::createFromString(self::UUID),
            $this->site,
            $this->netbillingBillerMapping
        );
    }
}
