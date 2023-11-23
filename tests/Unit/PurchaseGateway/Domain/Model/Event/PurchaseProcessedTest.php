<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model\Event;

use Probiller\Common\Enums\BusinessTransactionOperation\BusinessTransactionOperation;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use Tests\UnitTestCase;

class PurchaseProcessedTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $eventData;

    /**
     * @return void
     * @throws \Exception
     */
    private function generateEventData()
    {
        $this->eventData['purchaseId']            = $this->faker->uuid;
        $this->eventData['transactionCollection'] = [
            [
                'transactionId' => $this->faker->uuid,
                'state'         => 'approved'
            ]
        ];
        $this->eventData['sessionId']             = $this->faker->uuid;
        $this->eventData['siteId']                = $this->faker->uuid;
        $this->eventData['status']                = Transaction::STATUS_APPROVED;
        $this->eventData['memberId']              = $this->faker->uuid;
        $this->eventData['subscriptionId']        = $this->faker->uuid;
        $this->eventData['entrySiteId']           = $this->faker->uuid;
        $this->eventData['memberInfo']            = ['password' => 'test'];
        $this->eventData['selectedCrossSells']    = [];
        $this->eventData['crossSalePurchaseData'] = [];
        $this->eventData['payment']               = [];
        $this->eventData['itemId']                = $this->faker->uuid;
        $this->eventData['bundleId']              = $this->faker->uuid;
        $this->eventData['addOnId']               = $this->faker->uuid;
        $this->eventData['threeDRequired']        = false;
        $this->eventData['threedVersion']         = null;
        $this->eventData['threedFrictionless']    = false;
        $this->eventData['isThirdParty']          = false;
        $this->eventData['subscriptionUsername']  = 'test-purchase';
        $this->eventData['subscriptionPassword']  = 'xxx';
        $this->eventData['rebillFrequency']       = 365;
        $this->eventData['rebillStartDays']       = 365;
        $this->eventData['isTrial']               = true;
        $this->eventData['amount']                = $this->faker->randomFloat(2);
        $this->eventData['rebillAmount']          = $this->faker->randomFloat(2);
        $this->eventData['atlasCode']             = '';
        $this->eventData['atlasData']             = '';
        $this->eventData['ipAddress']             = $this->faker->ipv6;
        $this->eventData['tax']                   = null;
        $this->eventData['isExistingMember']      = false;
        $this->eventData['paymentMethod']         = null;
        $this->eventData['isNsf']                 = false;
        $this->eventData['trafficSource']         = 'ALL';
        $this->eventData['isUsernamePadded']      = false;
        $this->eventData['isImportedByApi']       = false;
        $this->eventData['skipVoidTransaction']   = false;
    }

    /**
     * @test
     * @return PurchaseProcessed
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_processed_object()
    {
        $this->generateEventData();
        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['purchaseId'],
            $this->eventData['transactionCollection'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['status'],
            $this->eventData['memberId'],
            $this->eventData['subscriptionId'],
            $this->eventData['memberInfo'],
            $this->eventData['crossSalePurchaseData'],
            $this->eventData['payment'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addOnId'],
            $this->eventData['threeDRequired'],
            $this->eventData['threedVersion'],
            $this->eventData['threedFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillFrequency'],
            $this->eventData['rebillStartDays'],
            $this->eventData['isTrial'],
            $this->eventData['amount'],
            $this->eventData['rebillAmount'],
            $this->eventData['atlasCode'],
            $this->eventData['atlasData'],
            $this->eventData['ipAddress'],
            $this->eventData['tax'],
            $this->eventData['isExistingMember'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['isUsernamePadded'],
            $this->eventData['skipVoidTransaction']
        );

        $this->assertInstanceOf(PurchaseProcessed::class, $purchaseProcessed);

        return $purchaseProcessed;
    }

    /**
     * @param PurchaseProcessed $purchaseProcessed PurchaseProcessed
     * @test
     * @depends it_should_return_a_valid_purchase_processed_object
     * @return void
     */
    public function to_array_should_contain_the_correct_keys($purchaseProcessed)
    {
        $purchaseProcessedArrayKeys = [
            'purchase_id',
            'transaction_collection',
            'session_id',
            'site_id',
            'status',
            'member_id',
            'member_info',
            'subscription_id',
            'entry_site_id',
            'cross_sale_purchase_data',
            'payment',
            'item_id',
            'bundle_id',
            'add_on_id',
            'subscription_username',
            'subscription_password',
            'rebill_frequency',
            'initial_days',
            'atlas_code',
            'atlas_data',
            'ip_address',
            'is_trial',
            'rebill_amount',
            'amounts',
            'is_existing_member',
            'entry_site_id',
            'payment_method',
            'traffic_source',
            'three_d_required',
            'threed_version',
            'threed_frictionless',
            'is_third_party',
            'is_nsf',
            'is_username_padded',
            'skip_void_transaction'
        ];

        $success = true;
        foreach ($purchaseProcessedArrayKeys as $key) {
            if (!array_key_exists($key, $purchaseProcessed->toArray())) {
                $success = false;
                break;
            }
        }

        $this->assertTrue($success);
    }



    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_SUBSCRIPTIONPURCHASE_when_initial_days_is_bigger_then_0(): void
    {
        $initialDays = 10;
        $this->generateEventData();
        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['purchaseId'],
            $this->eventData['transactionCollection'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['status'],
            $this->eventData['memberId'],
            $this->eventData['subscriptionId'],
            $this->eventData['memberInfo'],
            $this->eventData['crossSalePurchaseData'],
            $this->eventData['payment'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addOnId'],
            $this->eventData['threeDRequired'],
            $this->eventData['threedVersion'],
            $this->eventData['threedFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillFrequency'],
            $initialDays,
            $this->eventData['isTrial'],
            $this->eventData['amount'],
            $this->eventData['rebillAmount'],
            $this->eventData['atlasCode'],
            $this->eventData['atlasData'],
            $this->eventData['ipAddress'],
            $this->eventData['tax'],
            $this->eventData['isExistingMember'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['isUsernamePadded']
        );

        $this->assertEquals(BusinessTransactionOperation::SUBSCRIPTIONPURCHASE,
                            $purchaseProcessed->getBusinessTransactionOperationType());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_SUBSCRIPTIONPURCHASE_when_initial_days_in_crosssale_is_bigger_then_0(): void
    {
        $crossSalePurchaseData['initialDays']  = 10;
        $this->generateEventData();
        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['purchaseId'],
            $this->eventData['transactionCollection'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['status'],
            $this->eventData['memberId'],
            $this->eventData['subscriptionId'],
            $this->eventData['memberInfo'],
            $crossSalePurchaseData,
            $this->eventData['payment'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addOnId'],
            $this->eventData['threeDRequired'],
            $this->eventData['threedVersion'],
            $this->eventData['threedFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillFrequency'],
            $this->eventData['rebillStartDays'],
            $this->eventData['isTrial'],
            $this->eventData['amount'],
            $this->eventData['rebillAmount'],
            $this->eventData['atlasCode'],
            $this->eventData['atlasData'],
            $this->eventData['ipAddress'],
            $this->eventData['tax'],
            $this->eventData['isExistingMember'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['isUsernamePadded']
        );

        $this->assertEquals(BusinessTransactionOperation::SUBSCRIPTIONPURCHASE,
                            $purchaseProcessed->getBusinessTransactionOperationType($crossSalePurchaseData));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_true_for_subscriptionPurchaseIncludesNonRecurring_when_rebill_frequency_is_0_and_initial_days_greater_than_0(): void
    {
        $rebillFrequency = 0;
        $this->generateEventData();
        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['purchaseId'],
            $this->eventData['transactionCollection'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['status'],
            $this->eventData['memberId'],
            $this->eventData['subscriptionId'],
            $this->eventData['memberInfo'],
            $this->eventData['crossSalePurchaseData'],
            $this->eventData['payment'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addOnId'],
            $this->eventData['threeDRequired'],
            $this->eventData['threedVersion'],
            $this->eventData['threedFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $rebillFrequency,
            $this->eventData['rebillStartDays'],
            $this->eventData['isTrial'],
            $this->eventData['amount'],
            $this->eventData['rebillAmount'],
            $this->eventData['atlasCode'],
            $this->eventData['atlasData'],
            $this->eventData['ipAddress'],
            $this->eventData['tax'],
            $this->eventData['isExistingMember'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['isUsernamePadded']
        );

        $this->assertTrue($purchaseProcessed->subscriptionPurchaseIncludesNonRecurring());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_true_for_subscriptionPurchaseIncludesNonRecurring_when_rebill_days_is_0_and_initial_days_in_crosssale_is_greater_than_0(): void
    {
        $crossSalePurchaseData['rebillDays'] = 0;
        $crossSalePurchaseData['initialDays'] = 10;
        $this->generateEventData();
        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['purchaseId'],
            $this->eventData['transactionCollection'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['status'],
            $this->eventData['memberId'],
            $this->eventData['subscriptionId'],
            $this->eventData['memberInfo'],
            $crossSalePurchaseData,
            $this->eventData['payment'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addOnId'],
            $this->eventData['threeDRequired'],
            $this->eventData['threedVersion'],
            $this->eventData['threedFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillFrequency'],
            $this->eventData['rebillStartDays'],
            $this->eventData['isTrial'],
            $this->eventData['amount'],
            $this->eventData['rebillAmount'],
            $this->eventData['atlasCode'],
            $this->eventData['atlasData'],
            $this->eventData['ipAddress'],
            $this->eventData['tax'],
            $this->eventData['isExistingMember'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['isUsernamePadded']
        );

        $this->assertTrue($purchaseProcessed->subscriptionPurchaseIncludesNonRecurring($crossSalePurchaseData));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_SINGLECHARGEPURCHASE_when_rebill_frequency_and_initial_days_equals_0(): void
    {
        $initialDays     = 0;
        $this->generateEventData();
        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['purchaseId'],
            $this->eventData['transactionCollection'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['status'],
            $this->eventData['memberId'],
            $this->eventData['subscriptionId'],
            $this->eventData['memberInfo'],
            $this->eventData['crossSalePurchaseData'],
            $this->eventData['payment'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addOnId'],
            $this->eventData['threeDRequired'],
            $this->eventData['threedVersion'],
            $this->eventData['threedFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillFrequency'],
            $initialDays,
            $this->eventData['isTrial'],
            $this->eventData['amount'],
            $this->eventData['rebillAmount'],
            $this->eventData['atlasCode'],
            $this->eventData['atlasData'],
            $this->eventData['ipAddress'],
            $this->eventData['tax'],
            $this->eventData['isExistingMember'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['isUsernamePadded']
        );

        $this->assertEquals(BusinessTransactionOperation::SINGLECHARGEPURCHASE,
                            $purchaseProcessed->getBusinessTransactionOperationType());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_SINGLECHARGEPURCHASE_when_initial_days_in_crosssale_equals_0(): void
    {
        $crossSalePurchaseData['initialDays']  = 0;
        $this->generateEventData();
        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['purchaseId'],
            $this->eventData['transactionCollection'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['status'],
            $this->eventData['memberId'],
            $this->eventData['subscriptionId'],
            $this->eventData['memberInfo'],
            $crossSalePurchaseData,
            $this->eventData['payment'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addOnId'],
            $this->eventData['threeDRequired'],
            $this->eventData['threedVersion'],
            $this->eventData['threedFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillFrequency'],
            $this->eventData['rebillStartDays'],
            $this->eventData['isTrial'],
            $this->eventData['amount'],
            $this->eventData['rebillAmount'],
            $this->eventData['atlasCode'],
            $this->eventData['atlasData'],
            $this->eventData['ipAddress'],
            $this->eventData['tax'],
            $this->eventData['isExistingMember'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['isUsernamePadded']
        );

        $this->assertEquals(BusinessTransactionOperation::SINGLECHARGEPURCHASE,
                            $purchaseProcessed->getBusinessTransactionOperationType($crossSalePurchaseData));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_UNKNOWN_when_rebill_frequency_and_initial_days_equals_null(): void
    {
        $rebillFrequency = null;
        $initialDays     = null;

        $this->generateEventData();
        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['purchaseId'],
            $this->eventData['transactionCollection'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['status'],
            $this->eventData['memberId'],
            $this->eventData['subscriptionId'],
            $this->eventData['memberInfo'],
            $this->eventData['crossSalePurchaseData'],
            $this->eventData['payment'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addOnId'],
            $this->eventData['threeDRequired'],
            $this->eventData['threedVersion'],
            $this->eventData['threedFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillFrequency'],
            $initialDays,
            $this->eventData['isTrial'],
            $this->eventData['amount'],
            $this->eventData['rebillAmount'],
            $this->eventData['atlasCode'],
            $this->eventData['atlasData'],
            $this->eventData['ipAddress'],
            $this->eventData['tax'],
            $this->eventData['isExistingMember'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['isUsernamePadded']
        );

        $this->assertEquals(BusinessTransactionOperation::UNKNOWN,
                            $purchaseProcessed->getBusinessTransactionOperationType());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_UNKNOWN_when_rebill_frequency_is_negative(): void
    {
        $initialDays = -1;
        $this->generateEventData();
        $purchaseProcessed = new PurchaseProcessed(
            $this->eventData['purchaseId'],
            $this->eventData['transactionCollection'],
            $this->eventData['sessionId'],
            $this->eventData['siteId'],
            $this->eventData['status'],
            $this->eventData['memberId'],
            $this->eventData['subscriptionId'],
            $this->eventData['memberInfo'],
            $this->eventData['crossSalePurchaseData'],
            $this->eventData['payment'],
            $this->eventData['itemId'],
            $this->eventData['bundleId'],
            $this->eventData['addOnId'],
            $this->eventData['threeDRequired'],
            $this->eventData['threedVersion'],
            $this->eventData['threedFrictionless'],
            $this->eventData['isThirdParty'],
            $this->eventData['isNsf'],
            $this->eventData['rebillFrequency'],
            $initialDays,
            $this->eventData['isTrial'],
            $this->eventData['amount'],
            $this->eventData['rebillAmount'],
            $this->eventData['atlasCode'],
            $this->eventData['atlasData'],
            $this->eventData['ipAddress'],
            $this->eventData['tax'],
            $this->eventData['isExistingMember'],
            $this->eventData['entrySiteId'],
            $this->eventData['paymentMethod'],
            $this->eventData['trafficSource'],
            $this->eventData['isUsernamePadded']
        );

        $this->assertEquals(BusinessTransactionOperation::UNKNOWN,
                            $purchaseProcessed->getBusinessTransactionOperationType());
    }
}
