<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\ConfigService;

use Grpc\ChannelCredentials;
use Probiller\Service\Config\ProbillerConfigClient;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\PaymentTemplateValidation\PaymentTemplateValidationTranslatingService;
use Tests\IntegrationTestCase;

class PaymentTemplateValidationTranslatingServiceTest  extends IntegrationTestCase
{
    /**
     * !!!This site is already set up for dev env, but it can change.
     */
    const SITE_ID = "492ef60f-20e6-4c5c-8f7b-6862735cd9a1";
    /**
     * @var PaymentTemplateValidationTranslatingService
     */
    private $paymentTemplateValidationTranslatingService;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = new ConfigService(
            new ProbillerConfigClient(
                env('CONFIG_SERVICE_HOST', 'host.docker.internal:5000'),
                ['credentials' => ChannelCredentials::createInsecure()]
            )
        );

        $this->paymentTemplateValidationTranslatingService = new PaymentTemplateValidationTranslatingService($config);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_call_payment_template_validation(): void
    {
        $paymentTemplateCollection = new PaymentTemplateCollection();
        $templateId = $this->faker->uuid;
        $paymentTemplate = PaymentTemplate::create(
            $templateId,
            null,
            null,
            null,
            null,
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'rocketgate',
            []
        );

        $paymentTemplateCollection->add($paymentTemplate);

        $this->paymentTemplateValidationTranslatingService->retrieveAdviceFromConfig(
            $paymentTemplateCollection,
            SELF::SITE_ID,
            10
        );

        $this->assertTrue($paymentTemplateCollection->toArray()[0]['requiresIdentityVerification']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_call_payment_template_validation_for_single_charge_with_initial_days_zero(): void
    {
        $paymentTemplateCollection = new PaymentTemplateCollection();
        $templateId = $this->faker->uuid;
        $paymentTemplate = PaymentTemplate::create(
            $templateId,
            null,
            null,
            null,
            null,
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'rocketgate',
            []
        );

        $paymentTemplateCollection->add($paymentTemplate);

        $this->paymentTemplateValidationTranslatingService->retrieveAdviceFromConfig(
            $paymentTemplateCollection,
            SELF::SITE_ID,
            0
        );

        $this->assertTrue($paymentTemplateCollection->toArray()[0]['requiresIdentityVerification']);
    }
}
