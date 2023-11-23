<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
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
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingUnknownBillerException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\RocketgateBinRoutingAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeErrorException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\RocketgateBinRoutingTranslatingService;
use Tests\UnitTestCase;

class BinRoutingRocketgateTranslatingServiceTest extends UnitTestCase
{
    /**
     * @var RocketgateBinRoutingAdapter|MockObject
     */
    private $rocketgateBinRoutingAdapterMock;

    /**
     * @var RocketgateBinRoutingTranslatingService
     */
    private $rocketgateBinRoutingTranslatingService;

    /**
     * string
     */
    const UUID = 'db577af6-b2ae-11e9-a2a3-2a2ae2dbcce4';

    /**
     * @var MockObject|Site
     */
    private $site;

    /**
     * @var BusinessGroupId
     */
    private $businessGroupId;

    /**
     * @var BillerMapping
     */
    private $rocketgateBillerMapping;

    const UUID_2 = 'f291f110-d1a0-331d-a245-bc71fb168ce0';

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

        $this->rocketgateBinRoutingAdapterMock = $this->createMock(RocketgateBinRoutingAdapter::class);
        $this->rocketgateBinRoutingAdapterMock->method('retrieve')->willReturn(new BinRoutingCollection());

        $this->rocketgateBillerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            $this->businessGroupId,
            'USD',
            'rocketgate',
            RocketgateBillerFields::create(
                '12345',
                $_ENV['ROCKETGATE_MERCHANT_PASSWORD_1'],
                '12345',
                'sharedSecret',
                true
            )
        );

        $this->purchaseProcessMockWithRocketgateSettings();
    }

    /**
     * @return PurchaseProcess|MockObject
     * @throws ValidationException
     * @throws Exception
     * @throws \Exception
     */
    private function purchaseProcessMockWithRocketgateSettings(): PurchaseProcess
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
     * @throws \Exception
     */
    public function it_should_return_a_bin_routing_collection(): void
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

        $rocketgateBinRoutingAdapterMock = $this->createMock(RocketgateBinRoutingAdapter::class);

        $rocketgateBinRoutingAdapterMock->method('retrieve')->willReturn($binCollection);

        $this->rocketgateBinRoutingTranslatingService = new RocketgateBinRoutingTranslatingService(
            $rocketgateBinRoutingAdapterMock
        );

        $purchaseProcessMock = $this->purchaseProcessMockWithRocketgateSettings();
        $purchaseProcessMock->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new NetbillingBiller()]))
        );
        $purchaseProcessMock->method('gatewaySubmitNumber')->willReturn(0);

        $result = $this->rocketgateBinRoutingTranslatingService->retrieveRoutingCodes(
            $purchaseProcessMock,
            ItemId::createFromString(self::UUID),
            $this->site,
            $this->rocketgateBillerMapping
        );

        $this->assertInstanceOf(BinRoutingCollection::class, $result);
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
    public function it_should_send_join_submit_number_one_on_first_attempt()
    {
        $this->rocketgateBinRoutingAdapterMock->expects($this->once())->method('retrieve')->with(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            1,
            $this->anything(),
            (string) $this->businessGroupId->value()
        );

        $this->rocketgateBinRoutingTranslatingService = new RocketgateBinRoutingTranslatingService($this->rocketgateBinRoutingAdapterMock);

        $billerMock = $this->createMock(RocketgateBiller::class);
        $billerMock->method('name')->willReturn('anotherBiller');

        $purchaseProcessMock = $this->purchaseProcessMockWithRocketgateSettings();
        $purchaseProcessMock->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection([$billerMock]),
                $billerMock,
                1
            )
        );
        $purchaseProcessMock->method('gatewaySubmitNumber')->willReturn(0);

        $this->rocketgateBinRoutingTranslatingService->retrieveRoutingCodes(
            $purchaseProcessMock,
            ItemId::createFromString(self::UUID),
            $this->site,
            $this->rocketgateBillerMapping
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
    public function it_should_send_join_submit_number_two_on_second_attempt()
    {
        $this->rocketgateBinRoutingAdapterMock->expects($this->once())->method('retrieve')->with(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            2,
            $this->anything(),
            (string) $this->businessGroupId->value()
        );

        $this->rocketgateBinRoutingTranslatingService = new RocketgateBinRoutingTranslatingService($this->rocketgateBinRoutingAdapterMock);

        $purchaseProcessMock = $this->purchaseProcessMockWithRocketgateSettings();
        $purchaseProcessMock->method('gatewaySubmitNumber')->willReturn(1);
        $purchaseProcessMock->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection([new RocketgateBiller()]),
                new RocketgateBiller(),
                2
            )
        );

        $this->rocketgateBinRoutingTranslatingService->retrieveRoutingCodes(
            $purchaseProcessMock,
            ItemId::createFromString(self::UUID),
            $this->site,
            $this->rocketgateBillerMapping
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_get_first_six_from_selected_payment_template_for_sec_rev()
    {

        $this->rocketgateBinRoutingAdapterMock->expects($this->once())->method('retrieve')->with(
            '111111',
            $this->anything(),
            $this->anything(),
            $this->anything(),
            $this->anything(),
            (string) $this->businessGroupId->value()
        );

        $this->rocketgateBinRoutingTranslatingService = new RocketgateBinRoutingTranslatingService($this->rocketgateBinRoutingAdapterMock);

        $purchaseProcessMock = $this->purchaseProcessMockWithRocketgateSettings();

        $purchaseProcessMock->method('paymentTemplateCollection')->willReturn(new PaymentTemplateCollection());
        $paymentTemplate = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('firstSix')->willReturn('111111');
        $purchaseProcessMock->method('retrieveSelectedPaymentTemplate')
            ->willReturn($paymentTemplate);
        $purchaseProcessMock->method('gatewaySubmitNumber')->willReturn(1);
        $purchaseProcessMock->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $this->rocketgateBinRoutingTranslatingService->retrieveRoutingCodes(
            $purchaseProcessMock,
            ItemId::createFromString(self::UUID),
            $this->site,
            $this->rocketgateBillerMapping
        );
    }
}
