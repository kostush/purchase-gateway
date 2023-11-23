<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\PaymentTemplateValidation;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\PaymentTemplateValidation\PaymentTemplateValidationTranslator;
use Tests\UnitTestCase;

class PaymentTemplateValidationTranslatorTest extends UnitTestCase
{
    /** @var array */
    private $configServiceResponse;

    /** @var PaymentTemplateCollection */
    private $templateCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateCollection = new PaymentTemplateCollection();
        $this->templateCollection->offsetSet(
            0,
            PaymentTemplate::create(
                $this->faker->uuid,
                '123456',
                '1111',
                '2029',
                '11',
                (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'rocketgate',
                []
            )
        );

        $this->configServiceResponse = [
            "paymentTemplateValidationId"         => "a2405483-7767-4e09-a8aa-209261d2e571",
            "siteId"                              => "8e34c94e-135f-4acb-9141-58b3a6e56c74",
            "subscriptionPurchaseEnabled"         => false, //not required validation
            "subscriptionTrialUpgradeEnabled"     => true,
            "subscriptionUpgradeEnabled"          => true,
            "subscriptionExpiredRenewEnabled"     => false,
            "subscriptionRecurringChargeEnabled"  => true,
            "singleChargePurchaseEnabled"         => true, // required validation
            "recurringItemPurchaseEnabled"        => true,
            "recurringItemRecurringChargeEnabled" => true
        ];
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_all_payment_template_required_validation_for_single_charge(): void
    {
        $translator = new PaymentTemplateValidationTranslator();
        $translator::translate($this->templateCollection, $this->configServiceResponse, 0);

        $this->assertInstanceOf(PaymentTemplateCollection::class, $this->templateCollection);

        $this->assertSame(1, $this->templateCollection->count());

        foreach ($this->templateCollection as $value) {
            $templateArray = $value->toArray();

            $this->assertSame(
                $this->configServiceResponse['singleChargePurchaseEnabled'],
                $templateArray['requiresIdentityVerification']
            );
        }
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_all_payment_template_not_required_validation_for_subscription_purchase(): void
    {
        $translator = new PaymentTemplateValidationTranslator();
        $translator::translate($this->templateCollection, $this->configServiceResponse, 5);

        $this->assertInstanceOf(PaymentTemplateCollection::class, $this->templateCollection);

        $this->assertSame(1, $this->templateCollection->count());

        foreach ($this->templateCollection as $value) {
            $templateArray = $value->toArray();

            $this->assertSame(
                $this->configServiceResponse['subscriptionPurchaseEnabled'],
                $templateArray['requiresIdentityVerification']
            );
        }
    }
}