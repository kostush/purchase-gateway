<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\Processed\Member;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\Payment;
use ProBillerNG\PurchaseGateway\Application\BI\PurchasePending;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use Tests\UnitTestCase;

class PurchasePendingTest extends UnitTestCase
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
        return [
            'payment'             => null,
            'selectedCrossSells'  => [],
            'sessionId'           => 'test',
            'siteId'              => 'test',
            'itemId'              => 'test',
            'bundleId'            => 'test',
            'addonId'             => 'test',
            'status'              => 'test',
            'taxAmountInformed'   => 'No',
            'threeDRequired'      => false,
            'initialDays'         => 15,
            'initialAmount'       => 49.99,
            'rebillDays'          => 30,
            'rebillAmount'        => 89.99,
            'version'             => PurchasePending::LATEST_VERSION,
            'transactionId'       => 'test',
            'tax'                 => [],
            'entrySiteId'         => 'test',
            'paymentTemplate'     => null,
            'trafficSource'       => [],
            'fraudRecommendation' => FraudRecommendation::createDefaultAdvice()->toArray(),
            'fraudRecommendationCollection' => FraudRecommendation::createDefaultAdvice()->toArray()
        ];
    }

    /**
     * @test
     * @return PurchasePending
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_pending_object()
    {
        $this->eventData['memberInfo'] = $this->createMock(Member::class);
        $this->eventData['payment']    = $this->createMock(Payment::class);

        $purchasePendinged = new PurchasePending(
            $this->eventData['payment'],
            $this->eventData['selectedCrossSells'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addonId'],
            $this->eventData['status'],
            $this->eventData['taxAmountInformed'],
            $this->eventData['threeDRequired'],
            $this->eventData['initialDays'],
            $this->eventData['initialAmount'],
            $this->eventData['rebillDays'],
            $this->eventData['rebillAmount'],
            $this->eventData['transactionId'],
            $this->eventData['tax'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentTemplate'],
            $this->eventData['trafficSource'],
            $this->eventData['fraudRecommendation'],
            $this->eventData['fraudRecommendationCollection']
        );

        $this->assertInstanceOf(PurchasePending::class, $purchasePendinged);

        return $purchasePendinged;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_pending_object
     * @param PurchasePending $purchasePendinged PurchasePending
     * @return void
     */
    public function it_should_contain_the_correct_event_data($purchasePendinged)
    {
        $purchasePendingedEventData = $purchasePendinged->toArray();
        $success                    = true;

        foreach ($this->eventData as $k => $v) {
            if (!array_key_exists($k, $purchasePendingedEventData)) {
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
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCreditCardExpirationDate
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException
     * @throws \Throwable
     */
    public function create_for_new_cc_should_return_an_event(): void
    {
        $purchasePending = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $purchasePending->setPaymentInfo(
            NewCCPaymentInfo::create(
                (string) $this->faker->creditCardNumber,
                (string) $this->faker->numberBetween(100, 999),
                (string) $this->faker->numberBetween(1, 12),
                (string) $this->faker->numberBetween(2023, 2040),
                null
            )
        );

        $event = PurchasePending::createForNewCC($purchasePending);

        $this->assertInstanceOf(PurchasePending::class, $event);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function create_for_payment_template_should_return_an_event(): void
    {
        $purchasePending = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $paymentTemplateCollection = new PaymentTemplateCollection();
        $paymentTemplate           = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('isSelected')->willReturn(true);
        $paymentTemplate->method('firstSix')->willReturn("123456");
        $paymentTemplate->method('lastFour')->willReturn("1234");
        $paymentTemplate->method('billerFields')->willReturn([]);

        $paymentTemplateCollection->add($paymentTemplate);

        $purchasePending->setPaymentTemplateCollection($paymentTemplateCollection);

        $event = PurchasePending::createForPaymentTemplate($purchasePending);

        $this->assertInstanceOf(PurchasePending::class, $event);
    }
}
