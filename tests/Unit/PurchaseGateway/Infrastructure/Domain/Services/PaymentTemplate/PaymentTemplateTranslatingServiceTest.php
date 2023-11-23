<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplateServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplatesServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ValidatePaymentTemplateServiceAdapter;
use Tests\UnitTestCase;

class PaymentTemplateTranslatingServiceTest extends UnitTestCase
{
    /**
     * string
     */
    const UUID = 'db577af6-b2ae-11e9-a2a3-2a2ae2dbcce4';

    /**
     * string
     */
    const BILLER = 'rocketgate';

    /**
     * string
     */
    const PAYMENT_TYPE = 'cc';

    /**
     * @var RetrievePaymentTemplatesServiceAdapter
     */
    private $retrievePaymentTemplatesAdapterMock;

    /**
     * @var RetrievePaymentTemplateServiceAdapter
     */
    private $retrievePaymentTemplateAdapterMock;

    /**
     * @var ValidatePaymentTemplateServiceAdapter
     */
    private $validatePaymentTemplateAdapterMock;

    /**
     * @var PaymentTemplateTranslatingService
     */
    private $paymentTemplateTranslatingService;

    /**
     * Setup method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $paymentTemplate = PaymentTemplate::create(
            $this->faker->uuid,
            '123456',
            '1234',
            '2019',
            '11',
            '2019-08-11 15:15:25',
            '2019-08-11 15:15:25',
            'rocketgate',
            []
        );

        $secondPaymentTemplate = PaymentTemplate::create(
            $this->faker->uuid,
            '987654',
            '9876',
            '2019',
            '12',
            '2018-01-11 10:10:20',
            '2018-01-11 10:10:20',
            'rocketgate',
            []
        );

        $paymentTemplateCollection = new PaymentTemplateCollection();

        $paymentTemplateCollection->offsetSet(
            self::UUID . '_' . 1,
            $paymentTemplate
        );

        $paymentTemplateCollection->offsetSet(
            self::UUID . '_' . 2,
            $secondPaymentTemplate
        );

        $this->retrievePaymentTemplatesAdapterMock = $this->createMock(RetrievePaymentTemplatesServiceAdapter::class);
        $this->retrievePaymentTemplatesAdapterMock->method('retrieveAllPaymentTemplates')
            ->willReturn($paymentTemplateCollection);
        $this->retrievePaymentTemplateAdapterMock = $this->createMock(RetrievePaymentTemplateServiceAdapter::class);
        $this->retrievePaymentTemplateAdapterMock->method('retrievePaymentTemplate')->willReturn($paymentTemplate);
        $this->validatePaymentTemplateAdapterMock = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $this->validatePaymentTemplateAdapterMock->method('validatePaymentTemplate')->willReturn($paymentTemplate);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_payment_template_collection_when_retrieve_payment_templates_method_is_called(): void
    {
        $this->paymentTemplateTranslatingService = new PaymentTemplateTranslatingService(
            $this->retrievePaymentTemplatesAdapterMock,
            $this->retrievePaymentTemplateAdapterMock,
            $this->validatePaymentTemplateAdapterMock
        );

        $result = $this->paymentTemplateTranslatingService->retrieveAllPaymentTemplates(
            self::UUID,
            self::PAYMENT_TYPE,
            $this->faker->uuid
        );

        $this->assertInstanceOf(PaymentTemplateCollection::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_payment_template_when_retrieve_payment_template_method_is_called(): void
    {
        $this->paymentTemplateTranslatingService = new PaymentTemplateTranslatingService(
            $this->retrievePaymentTemplatesAdapterMock,
            $this->retrievePaymentTemplateAdapterMock,
            $this->validatePaymentTemplateAdapterMock
        );

        $result = $this->paymentTemplateTranslatingService->retrievePaymentTemplate(
            self::UUID,
            $this->faker->uuid
        );

        $this->assertInstanceOf(PaymentTemplate::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_payment_template_when_validate_payment_template_method_is_called(): void
    {
        $this->paymentTemplateTranslatingService = new PaymentTemplateTranslatingService(
            $this->retrievePaymentTemplatesAdapterMock,
            $this->retrievePaymentTemplateAdapterMock,
            $this->validatePaymentTemplateAdapterMock
        );

        $result = $this->paymentTemplateTranslatingService->validatePaymentTemplate(
            self::UUID,
            '1234',
            $this->faker->uuid
        );

        $this->assertInstanceOf(PaymentTemplate::class, $result);
    }
}
