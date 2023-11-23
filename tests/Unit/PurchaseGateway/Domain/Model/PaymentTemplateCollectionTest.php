<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use Illuminate\Support\Arr;
use ProbillerMGPG\SubsequentOperations\Init\Response\PaymentTemplateInfo;
use ProbillerMGPG\SubsequentOperations\Init\Response\UserFriendlyIdentifier;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use Tests\UnitTestCase;

class PaymentTemplateCollectionTest extends UnitTestCase
{
    /**
     * @var PaymentTemplateCollection
     */
    private $paymentTemplateCollection;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $rocketgateTemplate = $this->createMock(PaymentTemplate::class);
        $rocketgateTemplate->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $rocketgateTemplate->method('lastUsedDate')->willReturn('2020-08-20 00:00:00');
        $epochTemplate = $this->createMock(PaymentTemplate::class);
        $epochTemplate->method('billerName')->willReturn(EpochBiller::BILLER_NAME);
        $epochTemplate->method('lastUsedDate')->willReturn('2020-08-21 00:00:00');

        $paymentTemplateCollection = new PaymentTemplateCollection();
        $paymentTemplateCollection->add($rocketgateTemplate);
        $paymentTemplateCollection->add($epochTemplate);
        $this->paymentTemplateCollection = $paymentTemplateCollection;
    }

    /**
     * @test
     * @return PaymentTemplate
     */
    public function it_should_return_last_used_epoch_biller_payment_template_when_get_last_used_biller_template_is_called(): PaymentTemplate
    {
        $epochTemplate = $this->createMock(PaymentTemplate::class);
        $epochTemplate->method('billerName')->willReturn(EpochBiller::BILLER_NAME);
        $epochTemplate->method('lastUsedDate')->willReturn('2020-08-22 00:00:00');

        $paymentTemplateCollection = clone $this->paymentTemplateCollection;
        $paymentTemplateCollection->add($epochTemplate);

        $lastUsedTemplate = $paymentTemplateCollection->getLastUsedBillerTemplate(EpochBiller::BILLER_NAME);

        $this->assertInstanceOf(PaymentTemplate::class, $lastUsedTemplate);

        return $lastUsedTemplate;
    }

    /**
     * @test
     * @param PaymentTemplate $paymentTemplate Payment template.
     * @depends it_should_return_last_used_epoch_biller_payment_template_when_get_last_used_biller_template_is_called
     * @return void
     */
    public function it_should_have_epoch_biller_name(PaymentTemplate $paymentTemplate): void
    {
        $this->assertEquals(EpochBiller::BILLER_NAME, $paymentTemplate->billerName());
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_only_payment_templates_for_the_filtered_biller(): array
    {
        $paymentTemplateCollection = new PaymentTemplateCollection();

        $paymentTemplateCollection->offsetSet(
            $this->faker->uuid,
            PaymentTemplate::create(
                $this->faker->uuid,
                (string) random_int(400000, 499999),
                '',
                (string) random_int(2021, 2030),
                (string) random_int(01, 12),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                RocketgateBiller::BILLER_NAME,
                []
            )
        );

        $paymentTemplateCollection->offsetSet(
            $this->faker->uuid,
            PaymentTemplate::create(
                $this->faker->uuid,
                (string) random_int(400000, 499999),
                '',
                (string) random_int(2021, 2030),
                (string) random_int(01, 12),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                NetbillingBiller::BILLER_NAME,
                []
            )
        );

        $paymentTemplateCollection->offsetSet(
            $this->faker->uuid,
            PaymentTemplate::create(
                $this->faker->uuid,
                (string) random_int(400000, 499999),
                '',
                (string) random_int(2021, 2030),
                (string) random_int(01, 12),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                RocketgateBiller::BILLER_NAME,
                []
            )
        );

        $filteredPaymentTemplates = $paymentTemplateCollection->filterByBiller(
            RocketgateBiller::BILLER_NAME
        );

        $this->assertCount(2, $filteredPaymentTemplates);

        return $filteredPaymentTemplates;
    }

    /**
     * @test
     * @param array $paymentTemplates Payment Templates
     * @depends it_should_return_only_payment_templates_for_the_filtered_biller
     * @return void
     */
    public function it_should_contain_only_payment_templates_with_rocketgate_biller(array $paymentTemplates): void
    {
        foreach ($paymentTemplates as $paymentTemplate) {
            $this->assertSame(RocketgateBiller::BILLER_NAME, $paymentTemplate['billerName']);
        }
    }

    /**
     * @test
     * @param PaymentTemplate $paymentTemplate Payment template.
     * @depends it_should_return_last_used_epoch_biller_payment_template_when_get_last_used_biller_template_is_called
     * @return void
     */
    public function it_should_have_the_correct_last_used_date(PaymentTemplate $paymentTemplate): void
    {
        $this->assertEquals('2020-08-22 00:00:00', $paymentTemplate->lastUsedDate());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_null_when_get_last_used_biller_template_is_called(): void
    {
        $rocketgateTemplate = $this->createMock(PaymentTemplate::class);
        $rocketgateTemplate->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $rocketgateTemplate->method('lastUsedDate')->willReturn('2020-08-22 00:00:00');

        $paymentTemplateCollection = new PaymentTemplateCollection();
        $paymentTemplateCollection->add($rocketgateTemplate);

        $lastUsedTemplate = $paymentTemplateCollection->getLastUsedBillerTemplate(EpochBiller::BILLER_NAME);

        $this->assertNull($lastUsedTemplate);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_an_array()
    {
        $this->assertIsArray($this->paymentTemplateCollection->toArray());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function it_should_sort_the_payment_templates_desc_by_created_at(): void
    {
        $paymentTemplateCollection = new PaymentTemplateCollection();

        $today     = (new \DateTime('now'))->format('Y-m-d H:i:s');
        $yesterday = (new \DateTime('yesterday'))->format('Y-m-d H:i:s');
        $tomorrow  = (new \DateTime('tomorrow'))->format('Y-m-d H:i:s');

        $paymentTemplateCollection->offsetSet(
            $this->faker->uuid,
            PaymentTemplate::create(
                $this->faker->uuid,
                (string) random_int(400000, 499999),
                '',
                (string) random_int(2021, 2030),
                (string) random_int(01, 12),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                RocketgateBiller::BILLER_NAME,
                []
            )
        );

        $paymentTemplateCollection->offsetSet(
            $this->faker->uuid,
            PaymentTemplate::create(
                $this->faker->uuid,
                (string) random_int(400000, 499999),
                '',
                (string) random_int(2021, 2030),
                (string) random_int(01, 12),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                (new \DateTime('yesterday'))->format('Y-m-d H:i:s'),
                NetbillingBiller::BILLER_NAME,
                []
            )
        );

        $paymentTemplateCollection->offsetSet(
            $this->faker->uuid,
            PaymentTemplate::create(
                $this->faker->uuid,
                (string) random_int(400000, 499999),
                '',
                (string) random_int(2021, 2030),
                (string) random_int(01, 12),
                (new \DateTime('now'))->format('Y-m-d H:i:s'),
                (new \DateTime('tomorrow'))->format('Y-m-d H:i:s'),
                RocketgateBiller::BILLER_NAME,
                []
            )
        );

        $sortedPaymentTemplates = $paymentTemplateCollection->sortByCreatedAtDesc();

        $this->assertSame(
            [
                $tomorrow,
                $today,
                $yesterday
            ],
            [
                $sortedPaymentTemplates->first()->createdAt(),
                $sortedPaymentTemplates->next()->createdAt(),
                $sortedPaymentTemplates->last()->createdAt(),
            ]
        );
    }

    /**
     * @test
     */
    public function it_should_return_label_from_mgpg_ach_response_on_rebill_update_init(): void
    {
        $label = 'MyLabel';

        $userFriendlyIdentifier                      = new UserFriendlyIdentifier();
        $userFriendlyIdentifier->label               = 'MyLabel';
        $paymentTemplateInfo                         = new PaymentTemplateInfo();
        $paymentTemplateInfo->templateId             = $this->faker->uuid;
        $paymentTemplateInfo->userFriendlyIdentifier = $userFriendlyIdentifier;

        $paymentTemplateInfoArray[] = $paymentTemplateInfo;

        $response        = PaymentTemplateCollection::createFromRebillUpdateResponse($paymentTemplateInfoArray, false);
        $paymentTemplate = $response->firstPaymentTemplate();
        $this->assertEquals($label, $paymentTemplate->label());
    }

    /**
     * @test
     */
    public function it_should_properly_parse_sepa_payment_template(): void
    {
        $uuid = $this->faker->uuid;

        $paymentTemplateInfo = new PaymentTemplateInfo();
        $paymentTemplateInfo->templateId = $uuid;
        $paymentTemplateInfo->userFriendlyIdentifier = null;
        $paymentTemplateInfo->validationParameters = [];

        $collection = PaymentTemplateCollection::createFromRebillUpdateResponse([
            $paymentTemplateInfo
        ], true);

        $paymentCollectionItem = Arr::except($collection[0]->toArray(), ['lastUsedDate', 'createdAt']);

        $this->assertEquals([
            'templateId'                   => $uuid,
            'firstSix'                     => null,
            'expirationYear'               => null,
            'expirationMonth'              => null,
            'requiresIdentityVerification' => true,
            'identityVerificationMethod'   => 'last4',
        ],
        $paymentCollectionItem);
    }

}
