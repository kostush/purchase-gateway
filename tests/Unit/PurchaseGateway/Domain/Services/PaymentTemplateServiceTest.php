<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateId;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use Tests\UnitTestCase;

class PaymentTemplateServiceTest extends UnitTestCase
{
    private const TEMPLATE_ID         = '4c22fba2-f883-11e8-8eb2-f2801f1b9fff';
    private const FIRST_SIX           = '123456';
    private const LAST_FOUR           = '1234';
    private const EXPIRATION_YEAR     = '2019';
    private const EXPIRATION_MONTH    = '11';
    private const LAST_USED_DATE      = '2019-08-11 15:15:25';
    private const CREATED_AT          = '2019-08-11 15:15:25';
    private const BILLER_NAME         = 'rocketgate';
    private const BILLER_FIELDS       = [
        'cardHash'           => 'cardHashString',
        'merchantCustomerId' => '123456789'
    ];
    private const EPOCH_BILLER_FIELDS = [
        'memberId' => '12345'
    ];

    /**
     * @var PaymentTemplateCollection
     */
    private $paymentTemplateCollection;

    /**
     * @var PaymentTemplateCollection
     */
    private $thirdPartyBillerPaymentTemplateCollection;

    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * @var PaymentTemplateTranslatingService
     */
    private $paymentTemplateTranslatingService;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess                   = $this->createMock(PurchaseProcess::class);
        $this->paymentTemplateTranslatingService = $this->createMock(
            PaymentTemplateTranslatingService::class
        );

        $paymentTemplate = PaymentTemplate::create(
            self::TEMPLATE_ID,
            self::FIRST_SIX,
            self::LAST_FOUR,
            self::EXPIRATION_YEAR,
            self::EXPIRATION_MONTH,
            self::LAST_USED_DATE,
            self::CREATED_AT,
            self::BILLER_NAME,
            self::BILLER_FIELDS
        );

        $thirdPartyBillerPaymentTemplate = PaymentTemplate::create(
            self::TEMPLATE_ID,
            self::FIRST_SIX,
            self::LAST_FOUR,
            self::EXPIRATION_YEAR,
            self::EXPIRATION_MONTH,
            self::LAST_USED_DATE,
            self::CREATED_AT,
            EpochBiller::BILLER_NAME,
            self::EPOCH_BILLER_FIELDS
        );

        $paymentTemplateCollection = new PaymentTemplateCollection();
        $paymentTemplateCollection->offsetSet(
            self::TEMPLATE_ID,
            $paymentTemplate
        );

        $this->paymentTemplateCollection = $paymentTemplateCollection;

        $thirdPartyBilerPaymentTemplateCollection = new PaymentTemplateCollection();
        $thirdPartyBilerPaymentTemplateCollection->offsetSet(
            self::TEMPLATE_ID,
            $thirdPartyBillerPaymentTemplate
        );

        $this->thirdPartyBillerPaymentTemplateCollection = $thirdPartyBilerPaymentTemplateCollection;
    }

    /**
     * @test
     * @return void
     * @throws InvalidPaymentTemplateId
     * @throws Exception
     * @throws InvalidPaymentTemplateLastFour
     */
    public function it_should_throw_exception_when_template_id_not_found_in_collection(): void
    {
        $this->purchaseProcess->method('paymentTemplateCollection')->willReturn($this->paymentTemplateCollection);

        $paymentTemplateService = new PaymentTemplateService($this->paymentTemplateTranslatingService);

        $this->expectException(InvalidPaymentTemplateId::class);

        $paymentTemplateService->retrievePaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => '123456789',
                'lastFour'          => self::LAST_FOUR
            ]
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidPaymentTemplateId
     * @throws Exception
     * @throws InvalidPaymentTemplateLastFour
     */
    public function it_should_call_service_retrieve_method_when_payment_template_is_safe(): void
    {
        $this->paymentTemplateCollection
            ->get(self::TEMPLATE_ID)
            ->setIsSafe(true);

        $this->purchaseProcess->method('paymentTemplateCollection')->willReturn($this->paymentTemplateCollection);

        $paymentTemplateService = new PaymentTemplateService($this->paymentTemplateTranslatingService);

        $this->paymentTemplateTranslatingService->expects($this->once())->method('retrievePaymentTemplate');

        $paymentTemplateService->retrievePaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => self::LAST_FOUR
            ]
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidPaymentTemplateId
     * @throws InvalidPaymentTemplateLastFour
     * @throws Exception
     */
    public function it_should_throw_exception_when_invalid_length_last_four_provided(): void
    {
        $this->purchaseProcess->method('paymentTemplateCollection')->willReturn($this->paymentTemplateCollection);

        $paymentTemplateService = new PaymentTemplateService($this->paymentTemplateTranslatingService);

        $this->expectException(InvalidPaymentTemplateLastFour::class);

        $paymentTemplateService->retrievePaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => '999'
            ]
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidPaymentTemplateId
     * @throws InvalidPaymentTemplateLastFour
     * @throws Exception
     */
    public function it_should_throw_exception_when_invalid_characters_last_four_provided(): void
    {
        $this->purchaseProcess->method('paymentTemplateCollection')->willReturn($this->paymentTemplateCollection);

        $paymentTemplateService = new PaymentTemplateService($this->paymentTemplateTranslatingService);

        $this->expectException(InvalidPaymentTemplateLastFour::class);

        $paymentTemplateService->retrievePaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => 'abcd'
            ]
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidPaymentTemplateId
     * @throws Exception
     * @throws InvalidPaymentTemplateLastFour
     */
    public function it_should_call_service_validate_payment_template_method_when_last_four_are_valid(): void
    {
        $this->purchaseProcess->method('paymentTemplateCollection')->willReturn($this->paymentTemplateCollection);

        $paymentTemplateService = new PaymentTemplateService($this->paymentTemplateTranslatingService);

        $this->paymentTemplateTranslatingService->expects($this->once())->method('validatePaymentTemplate');

        $paymentTemplateService->retrievePaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => self::LAST_FOUR
            ]
        );
    }

    /**
     * @test
     * @return PaymentTemplate
     * @throws InvalidPaymentTemplateId
     * @throws InvalidPaymentTemplateLastFour
     * @throws Exception
     */
    public function it_should_return_a_payment_template_when_service_retrieve_is_successful(): PaymentTemplate
    {
        $this->paymentTemplateTranslatingService->method('validatePaymentTemplate')->willReturn(
            $this->paymentTemplateCollection->get(self::TEMPLATE_ID)
        );

        $this->purchaseProcess->method('paymentTemplateCollection')->willReturn($this->paymentTemplateCollection);

        $paymentTemplateService = new PaymentTemplateService($this->paymentTemplateTranslatingService);

        $retrievedPaymentTemplate = $paymentTemplateService->retrievePaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => self::LAST_FOUR
            ]
        );

        $this->assertInstanceOf(PaymentTemplate::class, $retrievedPaymentTemplate);

        return $retrievedPaymentTemplate;
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_when_service_retrieve_is_successful
     * @param PaymentTemplate $paymentTemplate PaymentTemplate
     * @return void
     */
    public function it_should_return_a_selected_payment_template_when_service_call_is_successful(
        PaymentTemplate $paymentTemplate
    ): void {
        $this->assertTrue($paymentTemplate->isSelected());
    }

    /**
     * @test
     * @return void
     * @throws InvalidPaymentTemplateId
     * @throws Exception
     */
    public function it_should_throw_exception_when_template_id_not_found_in_collection_when_retrieve_third_party_biller_payment_template_is_called(): void
    {
        $this->purchaseProcess->method('paymentTemplateCollection')->willReturn($this->thirdPartyBillerPaymentTemplateCollection);

        $paymentTemplateService = new PaymentTemplateService($this->paymentTemplateTranslatingService);

        $this->expectException(InvalidPaymentTemplateId::class);

        $paymentTemplateService->retrieveThirdPartyBillerPaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => '123456789',
            ]
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidPaymentTemplateId
     * @throws Exception
     */
    public function it_should_call_service_retrieve_method_when_retrieve_third_party_biller_payment_template_is_called(): void
    {
        $this->purchaseProcess->method('paymentTemplateCollection')->willReturn($this->thirdPartyBillerPaymentTemplateCollection);

        $paymentTemplateService = new PaymentTemplateService($this->paymentTemplateTranslatingService);

        $this->paymentTemplateTranslatingService->expects($this->once())->method('retrievePaymentTemplate');

        $paymentTemplateService->retrieveThirdPartyBillerPaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => self::TEMPLATE_ID
            ]
        );
    }

    /**
     * @test
     * @return PaymentTemplate
     * @throws InvalidPaymentTemplateId
     * @throws Exception
     */
    public function it_should_return_a_payment_template_when_service_retrieve_is_successful_when_retrieve_third_party_biller_payment_template_is_called(): PaymentTemplate
    {
        $this->purchaseProcess->method('paymentTemplateCollection')->willReturn($this->paymentTemplateCollection);

        $paymentTemplateService = new PaymentTemplateService($this->paymentTemplateTranslatingService);

        $retrievedPaymentTemplate = $paymentTemplateService->retrieveThirdPartyBillerPaymentTemplate(
            $this->purchaseProcess,
            [
                'paymentTemplateId' => self::TEMPLATE_ID
            ]
        );

        $this->assertInstanceOf(PaymentTemplate::class, $retrievedPaymentTemplate);

        return $retrievedPaymentTemplate;
    }
}
