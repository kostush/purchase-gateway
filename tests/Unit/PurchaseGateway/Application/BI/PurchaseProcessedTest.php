<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\Processed\AttemptedTransactions;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\Member;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\Payment;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProbillerNG\TransactionServiceClient\Model\CheckInformation;
use Tests\UnitTestCase;

class PurchaseProcessedTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $eventData;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->eventData = $this->returnEventData();
    }

    /**
     * @return array
     */
    private function returnEventData(): array
    {
        $attemptedTransactions = AttemptedTransactions::create(
            1,
            'rocketgate',
            Transaction::STATUS_APPROVED,
            [],
            [],
            []
        );

        $tax = [
            "initialAmount"    => [
                'beforeTaxes' => 0.5,
                'taxes'       => 0.5,
                'afterTaxes'  => 1,
            ],
            "rebillAmount"     => [
                "beforeTaxes" => 34.97,
                "taxes"       => 0.5,
                "afterTaxes"  => 34.97,
            ],
            "taxApplicationId" => "60bf5bcb-ac64-496c-acc5-9c7cf54a1869",
            "taxName"          => "VAT",
            "taxRate"          => 0.05,
            "taxType"          => "vat",
        ];
        $chargedAmountBeforeTaxes = 0.5;
        $chargedAmountAfterTaxes = 1;
        $chargedTaxAmount = 0.5;

        return [
            'memberInfo'                    => null,
            'payment'                       => null,
            'selectedCrossSells'            => [],
            'sessionId'                     => 'test',
            'siteId'                        => 'test',
            'itemId'                        => 'test',
            'bundleId'                      => 'test',
            'addonId'                       => 'test',
            'status'                        => 'test',
            'purchaseId'                    => 'test',
            'memberId'                      => 'test',
            'taxAmountInformed'             => 'No',
            'initialDays'                   => 15,
            'initialAmount'                 => 49.99,
            'threeDRequired'                => false,
            'threeDVersion'                 => null,
            'threeDFrictionless'            => null,
            'isThirdParty'                  => false,
            'isNsf'                         => false,
            'rebillDays'                    => 30,
            'rebillAmount'                  => 89.99,
            'subscriptionId'                => 'test',
            'version'                       => PurchaseProcessed::LATEST_VERSION,
            'transactionId'                 => 'test',
            'tax'                           => $tax,
            'chargedAmountBeforeTaxes'      => $chargedAmountBeforeTaxes,
            'chargedAmountAfterTaxes'       => $chargedAmountAfterTaxes,
            'chargedTaxAmount'              => $chargedTaxAmount,
            'entrySiteId'                   => 'test',
            'paymentTemplate'               => null,
            'existingMember'                => false,
            'atlasCode'                     => [],
            'fraudRecommendation'           => FraudRecommendation::createDefaultAdvice()->toArray(),
            'paymentMethod'                 => '',
            'trafficSource'                 => 'ALL',
            'cascadeAttemptedTransactions'         => $attemptedTransactions,
            'fraudRecommendationCollection' => [FraudRecommendation::createDefaultAdvice()->toArray()],
            'blacklistedInfo'               => [],
            'gatewayServiceFlags'           => ['overrideCascadeNetbillingReason3ds' => false]
        ];
    }

    /**
     * @test
     * @return PurchaseProcessed
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_processed_object()
    {
        $this->eventData['memberInfo'] = $this->createMock(Member::class);
        $this->eventData['payment']    = $this->createMock(Payment::class);

        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['memberInfo'],
            $this->eventData['payment'],
            $this->eventData['selectedCrossSells'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addonId'],
            $this->eventData['status'],
            $this->eventData['purchaseId'],
            $this->eventData['memberId'],
            $this->eventData['taxAmountInformed'],
            $this->eventData['initialDays'],
            $this->eventData['initialAmount'],
            $this->eventData['threeDRequired'],
            $this->eventData['threeDVersion'],
            $this->eventData['threeDFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillDays'],
            $this->eventData['rebillAmount'],
            $this->eventData['subscriptionId'],
            $this->eventData['transactionId'],
            $this->eventData['tax'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentTemplate'],
            $this->eventData['existingMember'],
            $this->eventData['cascadeAttemptedTransactions'],
            $this->eventData['atlasCode'],
            $this->eventData['fraudRecommendation'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['fraudRecommendationCollection'],
            $this->eventData['blacklistedInfo'],
            $this->eventData['gatewayServiceFlags']
        );

        $this->assertInstanceOf(PurchaseProcessed::class, $purchaseProcessed);

        return $purchaseProcessed;
    }

    /**
     * @test
     * @return PurchaseProcessed
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_processed_when_payment_is_null()
    {
        $this->eventData['memberInfo'] = $this->createMock(Member::class);

        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['memberInfo'],
            $this->eventData['payment'],
            $this->eventData['selectedCrossSells'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addonId'],
            $this->eventData['status'],
            $this->eventData['purchaseId'],
            $this->eventData['memberId'],
            $this->eventData['taxAmountInformed'],
            $this->eventData['initialDays'],
            $this->eventData['initialAmount'],
            $this->eventData['threeDRequired'],
            $this->eventData['threeDVersion'],
            $this->eventData['threeDFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillDays'],
            $this->eventData['rebillAmount'],
            $this->eventData['subscriptionId'],
            $this->eventData['transactionId'],
            $this->eventData['tax'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentTemplate'],
            $this->eventData['existingMember'],
            $this->eventData['cascadeAttemptedTransactions'],
            $this->eventData['atlasCode'],
            $this->eventData['fraudRecommendation'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['fraudRecommendationCollection'],
            $this->eventData['blacklistedInfo'],
            $this->eventData['gatewayServiceFlags']
        );

        $this->assertInstanceOf(PurchaseProcessed::class, $purchaseProcessed);

        return $purchaseProcessed;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_processed_object
     * @param PurchaseProcessed $purchaseProcessed PurchaseProcessed
     * @return void
     */
    public function it_should_contain_the_correct_event_data($purchaseProcessed)
    {
        $purchaseProcessedEventData = $purchaseProcessed->toArray();
        $success                    = true;

        foreach ($this->eventData as $k => $v) {
            if (!array_key_exists($k, $purchaseProcessedEventData)) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function create_for_new_cc_should_return_an_event(): void
    {
        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $year = date('Y') + 1;

        $purchaseProcess->setPaymentInfo(
            NewCCPaymentInfo::create(
                (string) $this->faker->creditCardNumber,
                (string) $this->faker->numberBetween(100, 999),
                (string) $this->faker->numberBetween(1, 12),
                (string) $year,
                null
            )
        );

        $event = PurchaseProcessed::createForNewCC($purchaseProcess);

        $this->assertInstanceOf(PurchaseProcessed::class, $event);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function create_for_payment_template_should_return_an_event(): void
    {
        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $paymentTemplateCollection = new PaymentTemplateCollection();
        $paymentTemplate           = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('isSelected')->willReturn(true);
        $paymentTemplate->method('firstSix')->willReturn("123456");
        $paymentTemplate->method('lastFour')->willReturn("1234");
        $paymentTemplate->method('billerFields')->willReturn([]);

        $paymentTemplateCollection->add($paymentTemplate);

        $purchaseProcess->setPaymentTemplateCollection($paymentTemplateCollection);

        $event = PurchaseProcessed::createForPaymentTemplate($purchaseProcess);

        $this->assertInstanceOf(PurchaseProcessed::class, $event);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function create_for_new_check_should_return_an_event(): void
    {
        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $purchaseProcess->setPaymentInfo(
            ChequePaymentInfo::create(
                (string) $this->faker->numberBetween(100, 999),
                (string) $this->faker->numberBetween(100, 999),
                false,
                (string) $this->faker->numberBetween(2030, 2040),
                ChequePaymentInfo::PAYMENT_TYPE,
                ChequePaymentInfo::PAYMENT_METHOD
            )
        );

        $event = PurchaseBiEventFactory::createForCheck($purchaseProcess);

        $this->assertInstanceOf(PurchaseProcessed::class, $event);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function checks_bi_event_should_show_only_last_four_digits_from_account_number(): void
    {
        $accountNumber = (string) $this->faker->numberBetween(100, 999);

        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $purchaseProcess->setPaymentInfo(
            ChequePaymentInfo::create(
                (string) $this->faker->numberBetween(100, 999),
                $accountNumber,
                false,
                (string) $this->faker->numberBetween(2030, 2040),
                ChequePaymentInfo::PAYMENT_TYPE,
                ChequePaymentInfo::PAYMENT_METHOD
            )
        );

        $event = PurchaseBiEventFactory::createForCheck($purchaseProcess);
        $accountNumberFromBiEvent = $event->getValue()['payment']['accountNumber'];

        $this->assertEquals(ChequePaymentInfo::obfuscateAccountNumber($accountNumber) , $accountNumberFromBiEvent);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_processed_object_with_threeDchallenged(): void
    {
        $purchaseProcessed = $this->it_should_return_a_valid_purchase_processed_object();
        $res_array = $purchaseProcessed->toArray();

        $this->assertArrayHasKey('threeDchallenged', $res_array);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_processed_object_when_payment_is_null_with_threeDchallenged(): void
    {
        $purchaseProcessed = $this->it_should_return_a_valid_purchase_processed_when_payment_is_null();
        $res_array = $purchaseProcessed->toArray();

        $this->assertArrayHasKey('threeDchallenged', $res_array);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_threeDchallenged_true_for_version_1_and_processed(): void
    {
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);
        foreach ($sessionPayload["initializedItemCollection"] as $key => $initializedItemCollection) {
            $sessionPayload["initializedItemCollection"][$key]['transactionCollection'][0]['threeDVersion'] = 1;
        }
        $sessionPayload['state'] = 'processed';
        $purchaseProcess = PurchaseProcess::restore($sessionPayload);

        $purchaseProcess->setPaymentInfo(
            NewCCPaymentInfo::create(
                (string) $this->faker->creditCardNumber,
                (string) $this->faker->numberBetween(100, 999),
                (string) $this->faker->numberBetween(1, 12),
                (string) $this->faker->numberBetween(2021, 2040),
                null
            )
        );
        $purchaseProcess->fraudAdvice()->markForceThreeDOnProcess();

        $event = PurchaseProcessed::createForNewCC($purchaseProcess);
        $res_array = $event->toArray();

        $this->assertArrayHasKey('threeDchallenged', $res_array);
        $this->assertEquals(true, $res_array['threeDchallenged']);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_threeDchallenged_null_for_threeDRequired_false(): void
    {
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);
        $purchaseProcess = PurchaseProcess::restore($sessionPayload);

        $purchaseProcess->setPaymentInfo(
            NewCCPaymentInfo::create(
                (string) $this->faker->creditCardNumber,
                (string) $this->faker->numberBetween(100, 999),
                (string) $this->faker->numberBetween(1, 12),
                (string) $this->faker->numberBetween((int)date("Y")+1, 2040),
                null
            )
        );

        $event = PurchaseProcessed::createForNewCC($purchaseProcess);
        $res_array = $event->toArray();

        $this->assertArrayHasKey('threeDchallenged', $res_array);
        $this->assertEquals(null, $res_array['threeDchallenged']);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_threeDchallenged_false_for_version_2_and_processed_and_threeDFrictionless_true(): void
    {
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);
        foreach ($sessionPayload["initializedItemCollection"] as $key => $initializedItemCollection) {
            $sessionPayload["initializedItemCollection"][$key]['transactionCollection'][0]['threeDVersion'] = 2;
            $sessionPayload["initializedItemCollection"][$key]['transactionCollection'][0]['threeDFrictionless'] = true;
        }
        $sessionPayload['state'] = 'processed';
        $purchaseProcess = PurchaseProcess::restore($sessionPayload);

        $purchaseProcess->setPaymentInfo(
            NewCCPaymentInfo::create(
                (string) $this->faker->creditCardNumber,
                (string) $this->faker->numberBetween(100, 999),
                (string) $this->faker->numberBetween(1, 12),
                (string) $this->faker->numberBetween((int) date('Y')+1, 2040),
                null
            )
        );
        $purchaseProcess->fraudAdvice()->markForceThreeDOnProcess();

        $event = PurchaseProcessed::createForNewCC($purchaseProcess);
        $res_array = $event->toArray();

        $this->assertArrayHasKey('threeDchallenged', $res_array);
        $this->assertEquals(false, $res_array['threeDchallenged']);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_threeDchallenged_true_for_version_2_and_processed_and_threeDFrictionless_false(): void
    {
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);
        foreach ($sessionPayload["initializedItemCollection"] as $key => $initializedItemCollection) {
            $sessionPayload["initializedItemCollection"][$key]['transactionCollection'][0]['threeDVersion'] = 2;
            $sessionPayload["initializedItemCollection"][$key]['transactionCollection'][0]['threeDFrictionless'] = false;
        }
        $sessionPayload['state'] = 'processed';
        $purchaseProcess = PurchaseProcess::restore($sessionPayload);

        $purchaseProcess->setPaymentInfo(
            NewCCPaymentInfo::create(
                (string) $this->faker->creditCardNumber,
                (string) $this->faker->numberBetween(100, 999),
                (string) $this->faker->numberBetween(1, 12),
                (string) $this->faker->numberBetween(2025, 2040),
                null
            )
        );
        $purchaseProcess->fraudAdvice()->markForceThreeDOnProcess();

        $event = PurchaseProcessed::createForNewCC($purchaseProcess);
        $res_array = $event->toArray();

        $this->assertArrayHasKey('threeDchallenged', $res_array);
        $this->assertEquals(true, $res_array['threeDchallenged']);
    }
}
