<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\PurchaseInitialized;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use Tests\UnitTestCase;

class PurchaseInitializedTest extends UnitTestCase
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
        $fraudRecommendation  = FraudRecommendation::createDefaultAdvice()->toArray();
        $selectedCasecadeInfo = [
            [
                "submitNumber"           => 1,
                "numberOfAllowedSubmits" => 1,
                "billerName"             => "rocketgate"
            ],
            [
                "submitNumber"           => 2,
                "numberOfAllowedSubmits" => 1,
                "billerName"             => "netbilling"
            ]
        ];

        return [
            'sessionId'                     => 'test',
            'siteId'                        => 'test',
            'bundleId'                      => 'test',
            'addOns'                        => ['test'],
            'clientIp'                      => 'test',
            'amount'                        => 20.01,
            'initialDays'                   => 3,
            'rebillAmount'                  => 25.01,
            'rebillDays'                    => 3,
            'currency'                      => 'test',
            'paymentType'                   => 'test',
            'clientCountryCode'             => 'test',
            'tax'                           => [],
            'availableCrossSells'           => [],
            'taxAmountInformed'             => 'No',
            'memberId'                      => 'uuid',
            'subscriptionId'                => 'uuid',
            'entrySiteId'                   => '',
            'paymentTemplateInfo'           => [],
            'version'                       => PurchaseInitialized::LATEST_VERSION,
            'atlasCode'                     => [],
            'fraudRecommendation'           => $fraudRecommendation,
            'threeD'                        => ['test'],
            'paymentMethod'                 => 'visa',
            'trafficSource'                 => 'ALL',
            'fraudRecommendationCollection' => [$fraudRecommendation],
            'gatewayServiceFlags'           => ['overrideCascadeNetbillingReason3ds' => false],
            'selectedCascadeInfo'           => $selectedCasecadeInfo
        ];
    }


    /**
     * @test
     * @return PurchaseInitialized
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_initialized_object(): PurchaseInitialized
    {
        $purchaseInitialized = new PurchaseInitialized(
            $this->eventData['sessionId'],
            [
                'siteId'         => $this->eventData['siteId'],
                'bundleId'       => $this->eventData['bundleId'],
                'addonId'        => 'test',
                'subscriptionId' => $this->eventData['subscriptionId'],
                'initialAmount'  => $this->eventData['amount'],
                'initialDays'    => $this->eventData['initialDays'],
                'rebillDays'     => $this->eventData['rebillDays'],
                'rebillAmount'   => $this->eventData['rebillAmount'],
                'tax'            => $this->eventData['tax']
            ],
            $this->eventData['availableCrossSells'],
            $this->eventData['clientIp'],
            $this->eventData['currency'],
            $this->eventData['paymentType'],
            $this->eventData['clientCountryCode'],
            $this->eventData['memberId'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentTemplateInfo'],
            $this->eventData['atlasCode'],
            $this->eventData['fraudRecommendation'],
            $this->eventData['threeD'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['fraudRecommendationCollection'],
            $this->eventData['gatewayServiceFlags'],
            $this->eventData['selectedCascadeInfo']
        );

        $this->assertInstanceOf(PurchaseInitialized::class, $purchaseInitialized);

        return $purchaseInitialized;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_purchase_initialized_object
     * @param PurchaseInitialized $purchaseInitialized PurchaseInitialized
     * @return void
     */
    public function it_should_contain_the_correct_event_data(PurchaseInitialized $purchaseInitialized)
    {
        $purchaseInitializedEventData = $purchaseInitialized->toArray();
        $success                      = true;

        foreach ($this->eventData as $k => $v) {
            if (!isset($purchaseInitializedEventData[$k]) || $purchaseInitializedEventData[$k] !== $v) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }
}
